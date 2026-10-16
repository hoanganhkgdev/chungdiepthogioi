<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Tra cứu/kiểm tra trùng "Mã số giới tử" (cột masothe) khi nhập tay, import và sinh mã tự động. Chỉ thêm index. */
return new class extends Migration
{
    private string $table = 'nv4_vi_official_rows';

    public function up(): void
    {
        if (! Schema::hasTable($this->table)) {
            return;
        }
        $has = collect(DB::select("SHOW INDEX FROM `{$this->table}`"))->contains(fn ($i) => $i->Key_name === 'idx_official_masothe');
        if (! $has) {
            DB::statement("ALTER TABLE `{$this->table}` ADD INDEX `idx_official_masothe` (`masothe`(20))");
        }
    }

    public function down(): void
    {
        if (Schema::hasTable($this->table)) {
            try {
                DB::statement("ALTER TABLE `{$this->table}` DROP INDEX `idx_official_masothe`");
            } catch (\Throwable) {
            }
        }
    }
};
