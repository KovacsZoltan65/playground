<?php

namespace App\Support\Permissions;

final class PermissionGuards
{
    public const WEB = 'web';

    public static function default(): string
    {
        return self::WEB;
    }

    public static function all(): array
    {
        return [
            self::WEB,
        ];
    }

    public static function options(): array
    {
        return array_map(
            fn (string $guard) => [
                'label' => strtoupper($guard),
                'value' => $guard,
            ],
            self::all(),
        );
    }
}
