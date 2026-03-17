<?php

namespace App\Support\Permissions;

final class UserPermissions
{
    public const VIEW_ANY = 'users.viewAny';

    public const VIEW = 'users.view';

    public const CREATE = 'users.create';

    public const UPDATE = 'users.update';

    public const DELETE = 'users.delete';

    public const DELETE_ANY = 'users.deleteAny';

    public static function all(): array
    {
        return [
            self::VIEW_ANY,
            self::VIEW,
            self::CREATE,
            self::UPDATE,
            self::DELETE,
            self::DELETE_ANY,
        ];
    }
}
