<?php

namespace App\Models;

use App\Models\Concerns\FillsEmptyStrings;
use App\Support\Alias;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class Official extends Model
{
    use FillsEmptyStrings;

    protected $table = 'nv4_vi_official_rows';
    public $timestamps = false;
    protected $guarded = [];

    protected static function booted(): void
    {
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
        return $this->ho . ' ' . $this->ten;
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
