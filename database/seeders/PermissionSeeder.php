<?php

namespace Database\Seeders;

use App\Support\Permissions\ActivityLogPermissions;
use App\Support\Permissions\CompanyPermissions;
use App\Support\Permissions\EmployeePermissions;
use App\Support\Permissions\PermissionPermissions;
use App\Support\Permissions\RolePermissions;
use App\Support\Permissions\SidebarTipPagePermissions;
use App\Support\Permissions\UserPermissions;
use App\Support\Permissions\UserTemporaryPermissionPermissions;
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

            foreach (ActivityLogPermissions::all() as $permissionName) {
                Permission::findOrCreate($permissionName, 'web');
            }

            foreach (CompanyPermissions::all() as $permissionName) {
                Permission::findOrCreate($permissionName, 'web');
            }

            foreach (EmployeePermissions::all() as $permissionName) {
                Permission::findOrCreate($permissionName, 'web');
            }

            foreach (SidebarTipPagePermissions::all() as $permissionName) {
                Permission::findOrCreate($permissionName, 'web');
            }

            foreach (PermissionPermissions::all() as $permissionName) {
                Permission::findOrCreate($permissionName, 'web');
            }

            foreach (RolePermissions::all() as $permissionName) {
                Permission::findOrCreate($permissionName, 'web');
            }

            foreach (UserPermissions::all() as $permissionName) {
                Permission::findOrCreate($permissionName, 'web');
            }

            foreach (UserTemporaryPermissionPermissions::all() as $permissionName) {
                Permission::findOrCreate($permissionName, 'web');
            }
        });
    }
}
