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

    /** @return array{all:int,hidden:int,noimage:int,dup:int} */
    public static function tabCounts(): array
    {
        return Cache::remember(self::TAB_COUNTS_KEY, 600, fn () => [
            'all' => static::count(),
            'hidden' => static::where('status', 0)->count(),
            'noimage' => static::where('image', '')->count(),
            'dup' => static::withDup()->whereNotNull('dups.c')->count(),
        ]);
    }

    /** @var array<string,array<string,bool>> */
    private static array $dupMemo = [];

    /**
     * Có bản ghi khác cùng Chứng điệp số và cùng Giới đàn không? Chỉ kiểm tra các dòng của trang hiện tại
     * (một truy vấn nhỏ) thay vì gom nhóm cả bảng ở mỗi lần mở trang.
     */
    public static function isDuplicate(self $o, iterable $pageRecords): bool
    {
        $records = collect($pageRecords);
        $memoKey = md5($records->pluck('id')->implode(','));

        if (! isset(self::$dupMemo[$memoKey])) {
            $numbers = $records->pluck('chungdiepso')->map(fn ($n) => trim((string) $n))->filter()->unique()->values();
            $map = [];
            if ($numbers->isNotEmpty()) {
                DB::table((new static)->getTable())
                    ->selectRaw('chungdiepso, part_id, count(*) as c')
                    ->whereIn('chungdiepso', $numbers)
                    ->groupBy('chungdiepso', 'part_id')->havingRaw('count(*) > 1')->get()
                    ->each(function ($r) use (&$map) {
                        $map[mb_strtolower(trim($r->chungdiepso)).'|'.$r->part_id] = true;
                    });
            }
            self::$dupMemo = [$memoKey => $map];
        }

        return isset(self::$dupMemo[$memoKey][mb_strtolower(trim((string) $o->chungdiepso)).'|'.$o->part_id]);
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
     * Mã số giới tử ngẫu nhiên, cùng dạng với mã cũ (11 chữ số, bắt đầu bằng 0), bảo đảm chưa có bản ghi nào dùng.
     */
    public static function generateMasothe(): string
    {
        $table = (new static)->getTable();

        do {
            $code = '0'.str_pad((string) random_int(0, 9_999_999_999), 10, '0', STR_PAD_LEFT);
        } while (DB::table($table)->where('masothe', $code)->exists());

        return $code;
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

    /**
     * Thêm cột is_dup: bản ghi có cùng Chứng điệp số VÀ cùng Giới đàn với bản ghi khác.
     * Gom nhóm một lần rồi join, tránh tự join O(n²) trên bảng MyISAM không có index chungdiepso.
     */
    public function scopeWithDup(Builder $q): Builder
    {
        $t = $this->getTable();
        $dups = DB::table($t)->selectRaw('chungdiepso, part_id, count(*) as c')
            ->where('chungdiepso', '<>', '')->groupBy('chungdiepso', 'part_id')->havingRaw('count(*) > 1');

        return $q->select("$t.*")->selectRaw('dups.c is not null as is_dup')
            ->leftJoinSub($dups, 'dups', fn ($j) => $j->on("$t.chungdiepso", '=', 'dups.chungdiepso')->on("$t.part_id", '=', 'dups.part_id'));
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
