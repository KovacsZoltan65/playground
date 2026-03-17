<?php

namespace App\Policies;

use App\Models\User;
use App\Support\Permissions\UserPermissions;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(UserPermissions::VIEW_ANY);
    }

    public function view(User $user, User $targetUser): bool
    {
        return $user->can(UserPermissions::VIEW);
    }

    public function create(User $user): bool
    {
        return $user->can(UserPermissions::CREATE);
    }

    public function update(User $user, User $targetUser): bool
    {
        return $user->can(UserPermissions::UPDATE);
    }

    public function delete(User $user, User $targetUser): bool
    {
        return $user->can(UserPermissions::DELETE);
    }

    public function deleteAny(User $user): bool
    {
        return $user->can(UserPermissions::DELETE_ANY);
    }
}
