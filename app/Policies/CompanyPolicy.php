<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;
use App\Support\Permissions\CompanyPermissions;

class CompanyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(CompanyPermissions::VIEW_ANY);
    }

    public function view(User $user, Company $company): bool
    {
        return $user->can(CompanyPermissions::VIEW);
    }

    public function create(User $user): bool
    {
        return $user->can(CompanyPermissions::CREATE);
    }

    public function update(User $user, Company $company): bool
    {
        return $user->can(CompanyPermissions::UPDATE);
    }

    public function delete(User $user, Company $company): bool
    {
        return $user->can(CompanyPermissions::DELETE);
    }

    public function deleteAny(User $user): bool
    {
        return $user->can(CompanyPermissions::DELETE_ANY);
    }
}
