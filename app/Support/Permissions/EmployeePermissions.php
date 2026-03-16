<?php

namespace App\Support\Permissions;

final class EmployeePermissions
{
    public const VIEW_ANY = 'employees.viewAny';

    public const VIEW = 'employees.view';

    public const CREATE = 'employees.create';

    public const UPDATE = 'employees.update';

    public const DELETE = 'employees.delete';

    public const DELETE_ANY = 'employees.deleteAny';

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
