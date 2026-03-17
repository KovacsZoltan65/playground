<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use App\Support\Permissions\Roles;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Activitylog\Facades\Activity;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Activity::withoutLogs(function (): void {
            $this->call([
                PermissionSeeder::class,
                RoleSeeder::class,
                SidebarTipSeeder::class,
            ]);

            $user = User::factory()->create([
                'name' => 'Test Admin',
                'email' => 'test_admin@example.com',
            ]);

            $user->assignRole(Roles::ADMIN);

            $companies = Company::factory()->count(12)->create();

            Employee::factory()->count(24)->recycle($companies)->create();
        });
    }
}
