<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\LegacyRole;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportNukevietUsers extends Command
{
    protected $signature = 'users:import-nukeviet';

    protected $description = 'Chuyển tài khoản từ bảng nv4_users sang users (giữ mật khẩu cũ, tự đổi sang bcrypt khi đăng nhập lần đầu)';

    public function handle(): int
    {
        $created = $skipped = 0;

        $positions = DB::table('nv4_authors')->pluck('position', 'admin_id');

        foreach (DB::table('nv4_users')->where('active', 1)->orderBy('userid')->get() as $nv) {
            if (User::where('email', $nv->email)->exists()) {
                $skipped++;

                continue;
            }

            $user = new User(['name' => trim($nv->last_name.' '.$nv->first_name) ?: $nv->username, 'email' => $nv->email]);
            // Gán thẳng hash cũ, không qua cast 'hashed'
            $user->setRawAttributes(array_merge($user->getAttributes(), ['password' => $nv->password]));
            $user->role = LegacyRole::fromAuthor($positions[$nv->userid] ?? null);
            $user->email_verified_at = now();
            $user->save();
            $created++;
            $this->line("+ {$nv->username} <{$nv->email}> [{$user->role->getLabel()}]");
        }

        $this->info("Thêm mới: $created · Đã có (bỏ qua): $skipped");

        return self::SUCCESS;
    }
}
