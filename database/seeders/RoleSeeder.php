<?php

namespace Database\Seeders;

use App\Support\Permissions\CompanyPermissions;
use App\Support\Permissions\Roles;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::findOrCreate(Roles::ADMIN, 'web');
        $managerRole = Role::findOrCreate(Roles::MANAGER, 'web');
        $hrRole = Role::findOrCreate(Roles::HR, 'web');
        $userRole = Role::findOrCreate(Roles::USER, 'web');

        $adminRole->syncPermissions(CompanyPermissions::all());

        $managerRole->syncPermissions([
            CompanyPermissions::VIEW_ANY,
            CompanyPermissions::VIEW,
            CompanyPermissions::CREATE,
            CompanyPermissions::UPDATE,
            CompanyPermissions::DELETE,
            CompanyPermissions::DELETE_ANY,
        ]);

        $hrRole->syncPermissions([
            CompanyPermissions::VIEW_ANY,
            CompanyPermissions::VIEW,
            CompanyPermissions::CREATE,
            CompanyPermissions::UPDATE,
        ]);

        $userRole->syncPermissions([
            CompanyPermissions::VIEW_ANY,
            CompanyPermissions::VIEW,
        ]);
    }
}
