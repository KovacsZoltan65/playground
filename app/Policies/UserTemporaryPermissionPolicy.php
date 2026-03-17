<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserTemporaryPermission;
use App\Support\Permissions\UserTemporaryPermissionPermissions;

class UserTemporaryPermissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(UserTemporaryPermissionPermissions::VIEW_ANY);
    }

    public function view(User $user, UserTemporaryPermission $assignment): bool
    {
        return $user->can(UserTemporaryPermissionPermissions::VIEW);
    }

    public function create(User $user): bool
    {
        return $user->can(UserTemporaryPermissionPermissions::CREATE);
    }

    public function update(User $user, UserTemporaryPermission $assignment): bool
    {
        return $user->can(UserTemporaryPermissionPermissions::UPDATE);
    }

    public function delete(User $user, UserTemporaryPermission $assignment): bool
    {
        return $user->can(UserTemporaryPermissionPermissions::DELETE);
    }

    public function deleteAny(User $user): bool
    {
        return $user->can(UserTemporaryPermissionPermissions::DELETE_ANY);
    }
}
