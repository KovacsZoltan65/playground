<?php

namespace App\Policies;

use App\Models\User;
use App\Support\Permissions\PermissionPermissions;
use Spatie\Permission\Models\Permission;

class PermissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionPermissions::VIEW_ANY);
    }

    public function view(User $user, Permission $permission): bool
    {
        return $user->can(PermissionPermissions::VIEW);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionPermissions::CREATE);
    }

    public function update(User $user, Permission $permission): bool
    {
        return $user->can(PermissionPermissions::UPDATE);
    }

    public function delete(User $user, Permission $permission): bool
    {
        return $user->can(PermissionPermissions::DELETE);
    }

    public function deleteAny(User $user): bool
    {
        return $user->can(PermissionPermissions::DELETE_ANY);
    }
}
