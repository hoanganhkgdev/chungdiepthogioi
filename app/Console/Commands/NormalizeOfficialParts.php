<?php

namespace App\Console\Commands;

use App\Models\OfficialPart;
use Illuminate\Console\Command;

class NormalizeOfficialParts extends Command
{
    protected $signature = 'parts:normalize {--apply : Ghi thay đổi (mặc định chỉ liệt kê)}';

    protected $description = 'Chuẩn hóa giới đàn: bỏ ký tự lạ ở đầu tên, đưa alias về chữ thường';

    public function handle(): int
    {
        $changes = [];

        foreach (OfficialPart::orderBy('id')->get() as $p) {
            $title = trim(preg_replace('/^[\s·•\-]+/u', '', (string) $p->title));
            $alias = strtolower((string) $p->alias);

            if ($title !== $p->title || $alias !== $p->alias) {
                $changes[] = [$p->id, $p->title, $title, $p->alias, $alias];
                if ($this->option('apply')) {
                    $p->forceFill(['title' => $title, 'alias' => $alias])->save();
                }
            }
        }

        $this->table(['ID', 'Tên cũ', 'Tên mới', 'Alias cũ', 'Alias mới'], $changes);
        $this->info(count($changes).' giới đàn '.($this->option('apply') ? 'đã cập nhật.' : 'sẽ thay đổi (chạy lại với --apply để ghi).'));

        return self::SUCCESS;
    }
}
