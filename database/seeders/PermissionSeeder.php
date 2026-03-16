<?php

namespace Database\Seeders;

use App\Support\Permissions\CompanyPermissions;
use App\Support\Permissions\EmployeePermissions;
use App\Support\Permissions\SidebarTipPagePermissions;
use Illuminate\Database\Seeder;
use Spatie\Activitylog\Facades\Activity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        Activity::withoutLogs(function (): void {
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            foreach (CompanyPermissions::all() as $permissionName) {
                Permission::findOrCreate($permissionName, 'web');
            }

            foreach (EmployeePermissions::all() as $permissionName) {
                Permission::findOrCreate($permissionName, 'web');
            }

            foreach (SidebarTipPagePermissions::all() as $permissionName) {
                Permission::findOrCreate($permissionName, 'web');
            }
        });
    }
}
