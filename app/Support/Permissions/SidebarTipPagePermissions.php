<?php

namespace App\Support\Permissions;

final class SidebarTipPagePermissions
{
    public const VIEW_ANY = 'sidebarTipPages.viewAny';

    public const VIEW = 'sidebarTipPages.view';

    public const CREATE = 'sidebarTipPages.create';

    public const UPDATE = 'sidebarTipPages.update';

    public const DELETE = 'sidebarTipPages.delete';

    public static function all(): array
    {
        return [
            self::VIEW_ANY,
            self::VIEW,
            self::CREATE,
            self::UPDATE,
            self::DELETE,
        ];
    }
}
