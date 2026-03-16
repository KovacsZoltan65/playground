<?php

namespace App\Policies;

use App\Models\SidebarTipPage;
use App\Models\User;
use App\Support\Permissions\SidebarTipPagePermissions;

class SidebarTipPagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(SidebarTipPagePermissions::VIEW_ANY);
    }

    public function view(User $user, SidebarTipPage $sidebarTipPage): bool
    {
        return $user->can(SidebarTipPagePermissions::VIEW);
    }

    public function create(User $user): bool
    {
        return $user->can(SidebarTipPagePermissions::CREATE);
    }

    public function update(User $user, SidebarTipPage $sidebarTipPage): bool
    {
        return $user->can(SidebarTipPagePermissions::UPDATE);
    }

    public function delete(User $user, SidebarTipPage $sidebarTipPage): bool
    {
        return $user->can(SidebarTipPagePermissions::DELETE);
    }
}
