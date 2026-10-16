<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $auth): bool
    {
        return $auth->isAdmin();
    }

    public function view(User $auth, User $model): bool
    {
        return $auth->isAdmin();
    }

    public function create(User $auth): bool
    {
        return $auth->isAdmin();
    }

    public function update(User $auth, User $model): bool
    {
        return $auth->isAdmin();
    }

    /** Không tự xóa mình và không xóa Quản trị cuối cùng. */
    public function delete(User $auth, User $model): bool
    {
        if (! $auth->isAdmin() || $auth->is($model)) {
            return false;
        }

        return ! $model->isAdmin() || User::otherActiveAdminsExist($model);
    }

    public function deleteAny(User $auth): bool
    {
        return $auth->isAdmin();
    }
}
