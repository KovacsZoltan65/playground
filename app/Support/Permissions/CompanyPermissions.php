<?php

namespace App\Support\Permissions;

final class CompanyPermissions
{
    public const VIEW_ANY = 'companies.viewAny';

    public const VIEW = 'companies.view';

    public const CREATE = 'companies.create';

    public const UPDATE = 'companies.update';

    public const DELETE = 'companies.delete';

    public const DELETE_ANY = 'companies.deleteAny';

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
