<?php

namespace App\Policies;

use App\Models\User;
use App\Support\Permissions\RolePermissions;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(RolePermissions::VIEW_ANY);
    }

    public function view(User $user, Role $role): bool
    {
        return $user->can(RolePermissions::VIEW);
    }

    public function create(User $user): bool
    {
        return $user->can(RolePermissions::CREATE);
    }

    public function update(User $user, Role $role): bool
    {
        return $user->can(RolePermissions::UPDATE);
    }

    public function delete(User $user, Role $role): bool
    {
        return $user->can(RolePermissions::DELETE);
    }

    public function deleteAny(User $user): bool
    {
        return $user->can(RolePermissions::DELETE_ANY);
    }
}
