<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Bảng nv4_vi_official_rows của NukeViet không có index nào ngoài khóa chính, nên mọi truy vấn lọc/đếm theo
 * giới đàn hay gom nhóm theo số chứng điệp đều quét toàn bảng (hàng giây trên host). Chỉ THÊM index, không đổi dữ liệu.
 */
return new class extends Migration
{
    private string $table = 'nv4_vi_official_rows';

    public function up(): void
    {
        if (! Schema::hasTable($this->table)) {
            return;
        }

        $existing = collect(DB::select("SHOW INDEX FROM `{$this->table}`"))->pluck('Key_name')->all();

        if (! in_array('idx_official_part', $existing, true)) {
            DB::statement("ALTER TABLE `{$this->table}` ADD INDEX `idx_official_part` (`part_id`)");
        }
        if (! in_array('idx_official_cds_part', $existing, true)) {
            // Tiền tố 40 ký tự vì MyISAM giới hạn khóa 1000 byte (utf8mb4 = 4 byte/ký tự)
            DB::statement("ALTER TABLE `{$this->table}` ADD INDEX `idx_official_cds_part` (`chungdiepso`(40), `part_id`)");
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable($this->table)) {
            return;
        }
        foreach (['idx_official_part', 'idx_official_cds_part'] as $index) {
            try {
                DB::statement("ALTER TABLE `{$this->table}` DROP INDEX `{$index}`");
            } catch (\Throwable) {
            }
        }
    }
};
