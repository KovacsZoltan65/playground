<?php

namespace App\Support\Permissions;

final class Roles
{
    public const ADMIN = 'Admin';

    public const MANAGER = 'Manager';

    public const HR = 'HR';

    public const USER = 'User';

    public static function all(): array
    {
        return [
            self::ADMIN,
            self::MANAGER,
            self::HR,
            self::USER,
        ];
    }
}
