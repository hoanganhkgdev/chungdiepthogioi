<?php

namespace App\Policies;

use App\Models\Official;
use App\Models\User;

class OfficialPolicy
{
    public function viewAny(User $auth): bool
    {
        return true;
    }

    public function view(User $auth, Official $o): bool
    {
        return true;
    }

    public function create(User $auth): bool
    {
        return $auth->canEdit();
    }

    public function update(User $auth, Official $o): bool
    {
        return $auth->canEdit();
    }

    /** Chỉ Quản trị được xóa chứng điệp. */
    public function delete(User $auth, Official $o): bool
    {
        return $auth->isAdmin();
    }

    public function deleteAny(User $auth): bool
    {
        return $auth->isAdmin();
    }
}
