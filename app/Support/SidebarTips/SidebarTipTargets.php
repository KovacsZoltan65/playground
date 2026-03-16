<?php

namespace App\Support\SidebarTips;

final class SidebarTipTargets
{
    public static function options(): array
    {
        return [
            [
                'component' => 'Dashboard',
                'label_key' => 'Dashboard',
                'route_name' => 'dashboard',
            ],
            [
                'component' => 'Company/Index',
                'label_key' => 'Companies',
                'route_name' => 'companies.index',
            ],
            [
                'component' => 'Company/Create',
                'label_key' => 'Create company page',
                'route_name' => 'companies.create',
            ],
            [
                'component' => 'Company/Edit',
                'label_key' => 'Edit company page',
                'route_name' => 'companies.edit',
            ],
            [
                'component' => 'Profile/Edit',
                'label_key' => 'Profile',
                'route_name' => 'profile.edit',
            ],
        ];
    }

    public static function components(): array
    {
        return array_column(self::options(), 'component');
    }

    public static function componentForRouteName(?string $routeName): ?string
    {
        foreach (self::options() as $option) {
            if ($option['route_name'] === $routeName) {
                return $option['component'];
            }
        }

        return null;
    }

    public static function labelKeyForComponent(string $component): ?string
    {
        foreach (self::options() as $option) {
            if ($option['component'] === $component) {
                return $option['label_key'];
            }
        }

        return null;
    }
}
