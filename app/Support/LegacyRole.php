<?php

namespace App\Support;

use App\Enums\UserRole;

/** Quy đổi chức danh trong bảng nv4_authors của NukeViet sang vai trò mới. */
class LegacyRole
{
    public static function fromAuthor(?string $position): UserRole
    {
        if ($position === null) {
            return UserRole::Viewer; // có tài khoản nhưng không phải quản trị viên bên NukeViet
        }

        $p = mb_strtolower($position);

        return str_contains($p, 'nhập liệu') || str_contains($p, 'biên tập') ? UserRole::Editor : UserRole::Admin;
    }
}
