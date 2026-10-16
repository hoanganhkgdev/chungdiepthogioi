<?php

use App\Enums\UserRole;
use App\Support\LegacyRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default(UserRole::Viewer->value)->after('password');
            $table->boolean('is_active')->default(true)->after('role');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
        });

        // Tài khoản hiện có: lấy vai trò từ bảng quản trị viên NukeViet nếu có; còn lại (tài khoản tạo tay) là Quản trị.
        $legacy = Schema::hasTable('nv4_users') && Schema::hasTable('nv4_authors')
            ? DB::table('nv4_users as u')->leftJoin('nv4_authors as a', 'a.admin_id', '=', 'u.userid')
                ->select('u.email', 'a.position')->get()->keyBy('email')
            : collect();

        foreach (DB::table('users')->get() as $user) {
            $role = $legacy->has($user->email)
                ? LegacyRole::fromAuthor($legacy[$user->email]->position)
                : UserRole::Admin;

            DB::table('users')->where('id', $user->id)->update(['role' => $role->value]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'is_active', 'last_login_at']);
        });
    }
};
