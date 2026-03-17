<?php

namespace App\Support\Permissions;

final class UserTemporaryPermissionPermissions
{
    public const VIEW_ANY = 'userTemporaryPermissions.viewAny';

    public const VIEW = 'userTemporaryPermissions.view';

    public const CREATE = 'userTemporaryPermissions.create';

    public const UPDATE = 'userTemporaryPermissions.update';

    public const DELETE = 'userTemporaryPermissions.delete';

    public const DELETE_ANY = 'userTemporaryPermissions.deleteAny';

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
