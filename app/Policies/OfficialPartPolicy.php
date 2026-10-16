<?php

namespace App\Policies;

use App\Models\OfficialPart;
use App\Models\User;

class OfficialPartPolicy
{
    public function viewAny(User $auth): bool
    {
        return $auth->isAdmin();
    }

    public function view(User $auth, OfficialPart $p): bool
    {
        return $auth->isAdmin();
    }

    public function create(User $auth): bool
    {
        return $auth->isAdmin();
    }

    public function update(User $auth, OfficialPart $p): bool
    {
        return $auth->isAdmin();
    }

    public function delete(User $auth, OfficialPart $p): bool
    {
        return $auth->isAdmin();
    }

    public function deleteAny(User $auth): bool
    {
        return $auth->isAdmin();
    }

    public function reorder(User $auth): bool
    {
        return $auth->isAdmin();
    }
}
