<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;
use App\Support\Permissions\EmployeePermissions;

class EmployeePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(EmployeePermissions::VIEW_ANY);
    }

    public function view(User $user, Employee $employee): bool
    {
        return $user->can(EmployeePermissions::VIEW);
    }

    public function create(User $user): bool
    {
        return $user->can(EmployeePermissions::CREATE);
    }

    public function update(User $user, Employee $employee): bool
    {
        return $user->can(EmployeePermissions::UPDATE);
    }

    public function delete(User $user, Employee $employee): bool
    {
        return $user->can(EmployeePermissions::DELETE);
    }

    public function deleteAny(User $user): bool
    {
        return $user->can(EmployeePermissions::DELETE_ANY);
    }
}
