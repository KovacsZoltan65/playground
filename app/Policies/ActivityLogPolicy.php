<?php

namespace App\Policies;

use App\Models\User;
use App\Support\Permissions\ActivityLogPermissions;
use Spatie\Activitylog\Models\Activity;

class ActivityLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(ActivityLogPermissions::VIEW_ANY);
    }

    public function view(User $user, Activity $activity): bool
    {
        return $user->can(ActivityLogPermissions::VIEW);
    }
}
