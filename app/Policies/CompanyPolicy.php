<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;

class CompanyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->exists;
    }

    public function view(User $user, Company $company): bool
    {
        return $user->exists;
    }

    public function create(User $user): bool
    {
        return $user->exists;
    }

    public function update(User $user, Company $company): bool
    {
        return $user->exists;
    }

    public function delete(User $user, Company $company): bool
    {
        return $user->exists;
    }

    public function deleteAny(User $user): bool
    {
        return $user->exists;
    }
}
