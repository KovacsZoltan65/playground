<?php

namespace App\Support\Permissions;

final class ActivityLogPermissions
{
    public const VIEW_ANY = 'activityLogs.viewAny';

    public const VIEW = 'activityLogs.view';

    public static function all(): array
    {
        return [
            self::VIEW_ANY,
            self::VIEW,
        ];
    }
}
