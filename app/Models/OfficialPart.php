<?php

namespace App\Models;

use App\Models\Concerns\FillsEmptyStrings;
use App\Support\Alias;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class OfficialPart extends Model
{
    use FillsEmptyStrings;

    protected $table = 'nv4_vi_official_part';
    public $timestamps = false;
    protected $guarded = [];

    protected static function booted(): void
    {
        static::creating(function (self $p) {
            $p->status ??= 1;
            if (empty($p->weight)) {
                $p->weight = ((int) static::max('weight')) + 1;
            }
        });

        // Alias tự sinh khi lưu (chữ thường, không trùng) — không phụ thuộc thao tác trên form
        static::saving(function (self $p) {
            if (blank($p->alias)) {
                $base = strtolower(Alias::make((string) $p->title)) ?: 'giioi-dan';
                $alias = $base;
                for ($i = 2; static::where('alias', $alias)->where('id', '!=', $p->id ?? 0)->exists(); $i++) {
                    $alias = $base.'-'.$i;
                }
                $p->alias = $alias;
            }
        });
    }

    /** @var array<int,int>|null */
    private static ?array $counts = null;

    /** Số chứng điệp theo từng giới đàn, một truy vấn GROUP BY duy nhất (bảng MyISAM không có index part_id). */
    public static function officialCounts(): array
    {
        return self::$counts ??= DB::table((new Official)->getTable())
            ->selectRaw('part_id, count(*) as n')->groupBy('part_id')->pluck('n', 'part_id')
            ->map(fn ($n) => (int) $n)->all();
    }

    public static function resetOfficialCounts(): void
    {
        self::$counts = null;
    }

    /** Thêm cột officials_count (và bí danh oc.n) bằng một join gom nhóm thay cho 33 truy vấn con. */
    public function scopeWithOfficialsCount(Builder $q): Builder
    {
        $t = $this->getTable();
        $sub = DB::table((new Official)->getTable())->selectRaw('part_id, count(*) as n')->groupBy('part_id');

        return $q->select("$t.*")->selectRaw('coalesce(oc.n, 0) as officials_count')
            ->leftJoinSub($sub, 'oc', fn ($j) => $j->on("$t.id", '=', 'oc.part_id'));
    }

    public function officials(): HasMany
    {
        return $this->hasMany(Official::class, 'part_id');
    }

    public function emptyStringColumns(): array
    {
        return ['title', 'alias', 'office', 'address', 'phone', 'fax', 'website', 'email', 'note', 'subid'];
    }

    public function emptyZeroColumns(): array
    {
        return ['parentid', 'lev', 'numsub', 'sort', 'weight'];
    }
}
