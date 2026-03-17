<?php

namespace Database\Seeders;

use App\Support\Permissions\CompanyPermissions;
use App\Support\Permissions\EmployeePermissions;
use App\Support\Permissions\PermissionPermissions;
use App\Support\Permissions\RolePermissions;
use App\Support\Permissions\Roles;
use App\Support\Permissions\SidebarTipPagePermissions;
use Illuminate\Database\Seeder;
use Spatie\Activitylog\Facades\Activity;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Activity::withoutLogs(function (): void {
            $adminRole = Role::findOrCreate(Roles::ADMIN, 'web');
            $managerRole = Role::findOrCreate(Roles::MANAGER, 'web');
            $hrRole = Role::findOrCreate(Roles::HR, 'web');
            $userRole = Role::findOrCreate(Roles::USER, 'web');

            $adminRole->syncPermissions([
                ...CompanyPermissions::all(),
                ...EmployeePermissions::all(),
                ...PermissionPermissions::all(),
                ...RolePermissions::all(),
                ...SidebarTipPagePermissions::all(),
            ]);

            $managerRole->syncPermissions([
                CompanyPermissions::VIEW_ANY,
                CompanyPermissions::VIEW,
                CompanyPermissions::CREATE,
                CompanyPermissions::UPDATE,
                CompanyPermissions::DELETE,
                CompanyPermissions::DELETE_ANY,
                EmployeePermissions::VIEW_ANY,
                EmployeePermissions::VIEW,
                EmployeePermissions::CREATE,
                EmployeePermissions::UPDATE,
                EmployeePermissions::DELETE,
                EmployeePermissions::DELETE_ANY,
                SidebarTipPagePermissions::VIEW_ANY,
                SidebarTipPagePermissions::VIEW,
                SidebarTipPagePermissions::CREATE,
                SidebarTipPagePermissions::UPDATE,
                SidebarTipPagePermissions::DELETE,
            ]);

            $hrRole->syncPermissions([
                CompanyPermissions::VIEW_ANY,
                CompanyPermissions::VIEW,
                CompanyPermissions::CREATE,
                CompanyPermissions::UPDATE,
                EmployeePermissions::VIEW_ANY,
                EmployeePermissions::VIEW,
                EmployeePermissions::CREATE,
                EmployeePermissions::UPDATE,
                SidebarTipPagePermissions::VIEW_ANY,
                SidebarTipPagePermissions::VIEW,
            ]);

            $userRole->syncPermissions([
                CompanyPermissions::VIEW_ANY,
                CompanyPermissions::VIEW,
                EmployeePermissions::VIEW_ANY,
                EmployeePermissions::VIEW,
            ]);
        });
    }
}
