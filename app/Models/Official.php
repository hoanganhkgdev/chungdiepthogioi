<?php

namespace App\Models;

use App\Models\Concerns\FillsEmptyStrings;
use App\Support\Alias;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class Official extends Model
{
    use FillsEmptyStrings;

    protected $table = 'nv4_vi_official_rows';
    public $timestamps = false;
    protected $guarded = [];

    /** Số đếm các tab ở trang danh sách, cache ngắn và tự xóa khi dữ liệu đổi. */
    public const TAB_COUNTS_KEY = 'official.tab-counts';

    /** @return array{all:int,hidden:int,noimage:int} */
    public static function tabCounts(): array
    {
        return Cache::remember(self::TAB_COUNTS_KEY, 600, fn () => [
            'all' => static::count(),
            'hidden' => static::where('status', 0)->count(),
            'noimage' => static::where('image', '')->count(),
        ]);
    }

    /** Tách "Họ và tên" thành [họ và đệm, tên]: tên là từ cuối cùng (quy ước tên người Việt). */
    public static function splitFullName(string $full): array
    {
        $words = preg_split('/\s+/u', trim($full), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if ($words === []) {
            return ['', ''];
        }
        $ten = array_pop($words);

        return [implode(' ', $words), $ten];
    }

    /** Họ và tên đã chuẩn hóa khoảng trắng, dùng để hiển thị và so sánh. */
    public static function joinFullName(?string $ho, ?string $ten): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', trim((string) $ho).' '.trim((string) $ten)));
    }

    /**
     * Mã số giới tử = mã tỉnh (3 số) + 2 nếu Tỳ Kheo/Sa Di còn lại 3 + 2 số cuối năm đại giới đàn + 5 số ngẫu nhiên (10000-99999).
     * Không có mã tỉnh thì dùng dạng cũ (0 + 10 số ngẫu nhiên), chỉ để dự phòng khi tạo bản ghi bằng code.
     *
     * @throws \InvalidArgumentException khi thiếu năm
     */
    public static function generateMasothe(?string $province = null, ?string $thogioi = null, ?string $tochucngay = null, ?int $namCap = null): string
    {
        $table = (new static)->getTable();

        if ($province === null) {
            do {
                $code = '0'.str_pad((string) random_int(0, 9_999_999_999), 10, '0', STR_PAD_LEFT);
            } while (DB::table($table)->where('masothe', $code)->exists());

            return $code;
        }

        $norm = preg_replace('/[^a-z0-9]/', '', \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii((string) $thogioi)));
        $gender = in_array($norm, ['tykheo', 'sadi'], true) ? 2 : 3;

        // Năm: số 4 chữ số cuối cùng trong "Tổ chức ngày" (ví dụ 30/03-03/04/2026); không có thì lấy năm cấp
        $year = null;
        if (preg_match_all('/\d{4}/', (string) $tochucngay, $m) && $m[0] !== []) {
            $year = (int) end($m[0]);
        } elseif ($namCap && $namCap > 1900) {
            $year = $namCap;
        }
        if (! $year) {
            throw new \InvalidArgumentException('Thiếu năm để tạo Mã số giới tử: cần "Tổ chức ngày" có ghi năm hoặc "Năm cấp".');
        }
        $prefix = $province.$gender.substr((string) $year, -2);

        do {
            $code = $prefix.random_int(10000, 99999);
        } while (DB::table($table)->where('masothe', $code)->exists());

        return $code;
    }

    /**
     * Sinh mã cho dữ liệu form khi để trống Mã số giới tử; ném ValidationException (hiện lỗi ngay ở ô tương ứng) nếu thiếu thông tin.
     *
     * @param  array<string,mixed>  $data
     */
    public static function codeFromFormData(array $data): string
    {
        $part = OfficialPart::find((int) ($data['part_id'] ?? 0));
        if (blank($part?->province_code)) {
            throw \Illuminate\Validation\ValidationException::withMessages(['data.part_id' => $part
                ? 'Giới đàn "'.$part->title.'" chưa có mã tỉnh: vào mục Giới đàn để chọn mã tỉnh, hoặc tự nhập Mã số giới tử.'
                : 'Chọn Giới đàn để hệ thống tạo Mã số giới tử (hoặc tự nhập mã).']);
        }

        try {
            return static::generateMasothe($part->province_code, $data['thogioi'] ?? null, $data['tochucngay'] ?? null, (int) ($data['nam_cap'] ?? 0));
        } catch (\InvalidArgumentException $e) {
            throw \Illuminate\Validation\ValidationException::withMessages(['data.tochucngay' => $e->getMessage()]);
        }
    }

    /** Dòng của trang hiện tại có chung Mã số giới tử với bản ghi khác không? (một truy vấn nhỏ, có index) */
    public static function isDuplicateCode(self $o, iterable $pageRecords): bool
    {
        static $memo = [];
        $records = collect($pageRecords);
        $key = md5($records->pluck('id')->implode(','));

        if (! isset($memo[$key])) {
            $codes = $records->pluck('masothe')->map(fn ($c) => trim((string) $c))->filter(fn ($c) => $c !== '' && $c !== '0')->unique()->values();
            $memo = [$key => $codes->isEmpty() ? [] : DB::table((new static)->getTable())
                ->select('masothe')->whereIn('masothe', $codes)->groupBy('masothe')->havingRaw('count(*) > 1')
                ->pluck('masothe')->map(fn ($c) => mb_strtolower(trim($c)))->flip()->all()];
        }

        return isset($memo[$key][mb_strtolower(trim((string) $o->masothe))]);
    }

    protected static function booted(): void
    {
        // Để trống Mã số giới tử (nhập tay hoặc import) thì tự tạo mã mới không trùng
        static::saving(function (self $o) {
            $code = trim((string) $o->masothe);
            if ($code === '' || $code === '0') {
                $o->masothe = static::generateMasothe();
            }
        });

        static::saved(fn () => Cache::forget(self::TAB_COUNTS_KEY));
        static::deleted(fn () => Cache::forget(self::TAB_COUNTS_KEY));

        static::saving(function (self $o) {
            $now = time();
            if (! $o->exists && empty($o->addtime)) {
                $o->addtime = $now;
            }
            $o->edittime = $now;
            if ($o->ngay_cap && $o->thang_cap && $o->nam_cap) {
                $o->ngaycap = mktime(0, 0, 0, (int) $o->thang_cap, (int) $o->ngay_cap, (int) $o->nam_cap);
            }
        });
    }

    public function emptyStringColumns(): array
    {
        return ['masothe', 'chungdiepso', 'phapdanh', 'ho', 'ten', 'namsinh', 'noisinh', 'ngayxuatgia', 'noixuatgia',
            'diachi', 'thogioi', 'tochucngay', 'diachigioidan', 'image', 'daigioidan', 'htdandau', 'yetma', 'giaotho',
            'denhat', 'denhi', 'detam', 'detu', 'dengu', 'deluc', 'dethat'];
    }

    public function emptyZeroColumns(): array
    {
        return ['part_id', 'ngaycap', 'ngay_cap', 'thang_cap', 'nam_cap', 'addtime', 'edittime', 'weight', 'prior'];
    }

    public function part(): BelongsTo
    {
        return $this->belongsTo(OfficialPart::class, 'part_id');
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('status', 1);
    }

    public function getFullnameAttribute(): string
    {
        return static::joinFullName($this->ho, $this->ten);
    }

    public function slug(): string
    {
        return strtolower(Alias::make($this->ho . ' ' . $this->ten));
    }

    /** Đường dẫn công khai — đúng mẫu đã in trong QR: /official/{alias-giới-đàn}/{họ-tên}-{id} */
    public function path(): string
    {
        return '/official/' . ($this->part->alias ?? '') . '/' . $this->slug() . '-' . $this->id;
    }

    /** URL tuyệt đối dùng để sinh QR (domain cố định như bản cũ). */
    public function qrUrl(): string
    {
        return rtrim(config('app.qr_base_url'), '/') . $this->path();
    }
}
