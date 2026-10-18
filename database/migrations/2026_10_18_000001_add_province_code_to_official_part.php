<?php

use App\Support\ProvinceCodes;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Mỗi giới đàn có một mã tỉnh 3 số (như CCCD) để làm 3 số đầu của Mã số giới tử.
 * Chỉ THÊM cột mới; điền sẵn theo tên giới đàn, giới đàn không nhận ra (ví dụ Sông Bé) để trống cho admin chọn.
 */
return new class extends Migration
{
    private string $table = 'nv4_vi_official_part';

    public function up(): void
    {
        if (! Schema::hasTable($this->table) || Schema::hasColumn($this->table, 'province_code')) {
            return;
        }

        Schema::table($this->table, function (Blueprint $table) {
            $table->char('province_code', 3)->nullable();
        });

        foreach (DB::table($this->table)->get(['id', 'title']) as $part) {
            if ($code = ProvinceCodes::resolve($part->title)) {
                DB::table($this->table)->where('id', $part->id)->update(['province_code' => $code]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn($this->table, 'province_code')) {
            Schema::table($this->table, fn (Blueprint $table) => $table->dropColumn('province_code'));
        }
    }
};
