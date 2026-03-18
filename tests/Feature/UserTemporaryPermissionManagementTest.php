<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use App\Models\UserTemporaryPermission;
use App\Services\UserTemporaryPermissionService;
use App\Support\Permissions\CompanyPermissions;
use App\Support\Permissions\EmployeePermissions;
use App\Support\Permissions\Roles;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->seed([
        PermissionSeeder::class,
        RoleSeeder::class,
    ]);
});

function temporaryPermissionUserWithRole(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

function assignTemporaryPermission(User $user, string $permissionName, string $startsAt, string $endsAt, ?string $reason = null): UserTemporaryPermission
{
    $permission = Permission::findByName($permissionName, 'web');

    return UserTemporaryPermission::query()->create([
        'user_id' => $user->id,
        'permission_id' => $permission->id,
        'starts_at' => $startsAt,
        'ends_at' => $endsAt,
        'reason' => $reason,
    ]);
}

it('requires authentication for the temporary permissions page', function () {
    $this->get(route('user-temporary-permissions.index'))->assertRedirect(route('login'));
});

it('allows admins to list temporary permission assignments', function () {
    $user = temporaryPermissionUserWithRole(Roles::USER);
    assignTemporaryPermission($user, EmployeePermissions::UPDATE, '2026-03-20 08:00:00', '2026-03-25 18:00:00');

    $this->actingAs(temporaryPermissionUserWithRole(Roles::ADMIN))
        ->getJson(route('user-temporary-permissions.list'))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.user_name', $user->name)
        ->assertJsonPath('data.0.permission_name', EmployeePermissions::UPDATE);
});

it('forbids listing temporary permission assignments without access', function () {
    $this->actingAs(temporaryPermissionUserWithRole(Roles::USER))
        ->getJson(route('user-temporary-permissions.list'))
        ->assertForbidden();
});

it('returns effective user permission ids for create-page permission filtering', function () {
    $targetUser = temporaryPermissionUserWithRole(Roles::MANAGER);
    $permanentPermission = Permission::findByName(EmployeePermissions::UPDATE, 'web');
    $temporaryPermission = Permission::findByName(CompanyPermissions::UPDATE, 'web');

    assignTemporaryPermission(
        $targetUser,
        CompanyPermissions::UPDATE,
        '2026-03-17 08:00:00',
        '2026-03-20 18:00:00',
    );

    $this->travelTo(now()->setDate(2026, 3, 18)->setTime(12, 0));

    $ids = app(UserTemporaryPermissionService::class)->userEffectivePermissionIds()[$targetUser->id] ?? [];

    expect($ids)->toContain($permanentPermission->id);
    expect($ids)->toContain($temporaryPermission->id);
});

it('creates a temporary permission assignment', function () {
    $targetUser = temporaryPermissionUserWithRole(Roles::USER);
    $permission = Permission::findByName(EmployeePermissions::UPDATE, 'web');

    $this->actingAs(temporaryPermissionUserWithRole(Roles::ADMIN))
        ->postJson(route('user-temporary-permissions.store'), [
            'user_id' => $targetUser->id,
            'permission_id' => $permission->id,
            'starts_at' => '2026-03-20T08:00',
            'ends_at' => '2026-03-25T18:00',
            'reason' => 'Temporary coverage during absence.',
        ])
        ->assertCreated()
        ->assertJsonPath('data.user_id', $targetUser->id)
        ->assertJsonPath('data.permission_name', EmployeePermissions::UPDATE);

    $this->assertDatabaseHas('user_temporary_permissions', [
        'user_id' => $targetUser->id,
        'permission_id' => $permission->id,
        'reason' => 'Temporary coverage during absence.',
    ]);
});

it('updates a temporary permission assignment', function () {
    $targetUser = temporaryPermissionUserWithRole(Roles::USER);
    $permission = Permission::findByName(EmployeePermissions::UPDATE, 'web');
    $otherPermission = Permission::findByName(EmployeePermissions::DELETE, 'web');
    $assignment = assignTemporaryPermission(
        $targetUser,
        EmployeePermissions::UPDATE,
        '2026-03-20 08:00:00',
        '2026-03-25 18:00:00',
    );

    $this->actingAs(temporaryPermissionUserWithRole(Roles::ADMIN))
        ->putJson(route('user-temporary-permissions.update', $assignment), [
            'user_id' => $targetUser->id,
            'permission_id' => $otherPermission->id,
            'starts_at' => '2026-03-21T09:00',
            'ends_at' => '2026-03-26T17:00',
            'reason' => 'Adjusted window.',
        ])
        ->assertOk()
        ->assertJsonPath('data.permission_name', EmployeePermissions::DELETE)
        ->assertJsonPath('data.reason', 'Adjusted window.');

    $this->assertDatabaseHas('user_temporary_permissions', [
        'id' => $assignment->id,
        'permission_id' => $otherPermission->id,
        'reason' => 'Adjusted window.',
    ]);
});

it('deletes a temporary permission assignment', function () {
    $assignment = assignTemporaryPermission(
        temporaryPermissionUserWithRole(Roles::USER),
        EmployeePermissions::UPDATE,
        '2026-03-20 08:00:00',
        '2026-03-25 18:00:00',
    );

    $this->actingAs(temporaryPermissionUserWithRole(Roles::ADMIN))
        ->deleteJson(route('user-temporary-permissions.destroy', $assignment))
        ->assertOk();

    $this->assertSoftDeleted('user_temporary_permissions', [
        'id' => $assignment->id,
    ]);
});

it('bulk deletes temporary permission assignments', function () {
    $first = assignTemporaryPermission(
        temporaryPermissionUserWithRole(Roles::USER),
        EmployeePermissions::UPDATE,
        '2026-03-20 08:00:00',
        '2026-03-25 18:00:00',
    );
    $second = assignTemporaryPermission(
        temporaryPermissionUserWithRole(Roles::USER),
        EmployeePermissions::DELETE,
        '2026-03-26 08:00:00',
        '2026-03-30 18:00:00',
    );

    $this->actingAs(temporaryPermissionUserWithRole(Roles::ADMIN))
        ->deleteJson(route('user-temporary-permissions.bulk-destroy'), [
            'ids' => [$first->id, $second->id],
        ])
        ->assertOk()
        ->assertJsonPath('deleted', 2);

    $this->assertSoftDeleted('user_temporary_permissions', ['id' => $first->id]);
    $this->assertSoftDeleted('user_temporary_permissions', ['id' => $second->id]);
});

it('validates the target user', function () {
    $permission = Permission::findByName(EmployeePermissions::UPDATE, 'web');

    $this->actingAs(temporaryPermissionUserWithRole(Roles::ADMIN))
        ->postJson(route('user-temporary-permissions.store'), [
            'user_id' => 999999,
            'permission_id' => $permission->id,
            'starts_at' => '2026-03-20T08:00',
            'ends_at' => '2026-03-25T18:00',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['user_id']);
});

it('validates the target permission', function () {
    $targetUser = temporaryPermissionUserWithRole(Roles::USER);

    $this->actingAs(temporaryPermissionUserWithRole(Roles::ADMIN))
        ->postJson(route('user-temporary-permissions.store'), [
            'user_id' => $targetUser->id,
            'permission_id' => 999999,
            'starts_at' => '2026-03-20T08:00',
            'ends_at' => '2026-03-25T18:00',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['permission_id']);
});

it('validates the date range', function () {
    $targetUser = temporaryPermissionUserWithRole(Roles::USER);
    $permission = Permission::findByName(EmployeePermissions::UPDATE, 'web');

    $this->actingAs(temporaryPermissionUserWithRole(Roles::ADMIN))
        ->postJson(route('user-temporary-permissions.store'), [
            'user_id' => $targetUser->id,
            'permission_id' => $permission->id,
            'starts_at' => '2026-03-25T18:00',
            'ends_at' => '2026-03-20T08:00',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['ends_at']);
});

it('rejects overlapping temporary permission assignments for the same user and permission', function () {
    $targetUser = temporaryPermissionUserWithRole(Roles::USER);
    $permission = Permission::findByName(EmployeePermissions::UPDATE, 'web');

    assignTemporaryPermission(
        $targetUser,
        EmployeePermissions::UPDATE,
        '2026-03-20 08:00:00',
        '2026-03-25 18:00:00',
    );

    $this->actingAs(temporaryPermissionUserWithRole(Roles::ADMIN))
        ->postJson(route('user-temporary-permissions.store'), [
            'user_id' => $targetUser->id,
            'permission_id' => $permission->id,
            'starts_at' => '2026-03-24T09:00',
            'ends_at' => '2026-03-27T17:00',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['permission_id']);
});

it('filters assignments by status', function () {
    $user = temporaryPermissionUserWithRole(Roles::USER);

    assignTemporaryPermission($user, EmployeePermissions::UPDATE, '2026-03-10 08:00:00', '2026-03-12 18:00:00');
    assignTemporaryPermission($user, EmployeePermissions::DELETE, '2026-03-17 08:00:00', '2026-03-20 18:00:00');
    assignTemporaryPermission($user, CompanyPermissions::UPDATE, '2026-03-25 08:00:00', '2026-03-28 18:00:00');

    $this->travelTo(now()->setDate(2026, 3, 18)->setTime(12, 0));

    $this->actingAs(temporaryPermissionUserWithRole(Roles::ADMIN))
        ->getJson(route('user-temporary-permissions.list', ['status' => 'active']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.permission_name', EmployeePermissions::DELETE);
});

it('sorts temporary permission assignments by user name ascending', function () {
    $alphaUser = User::factory()->create(['name' => 'Alpha Temporary Sort']);
    $alphaUser->assignRole(Roles::USER);

    $zetaUser = User::factory()->create(['name' => 'Zeta Temporary Sort']);
    $zetaUser->assignRole(Roles::USER);

    assignTemporaryPermission(
        $zetaUser,
        EmployeePermissions::UPDATE,
        '2026-03-20 08:00:00',
        '2026-03-25 18:00:00',
        'sort-temporary-users',
    );

    assignTemporaryPermission(
        $alphaUser,
        EmployeePermissions::UPDATE,
        '2026-03-21 08:00:00',
        '2026-03-26 18:00:00',
        'sort-temporary-users',
    );

    $this->actingAs(temporaryPermissionUserWithRole(Roles::ADMIN))
        ->getJson(route('user-temporary-permissions.list', [
            'search' => 'sort-temporary-users',
            'sort_field' => 'user_name',
            'sort_direction' => 'asc',
        ]))
        ->assertOk()
        ->assertJsonPath('data.0.user_name', 'Alpha Temporary Sort')
        ->assertJsonPath('data.1.user_name', 'Zeta Temporary Sort');
});

it('allows a protected action when a user has an active temporary permission', function () {
    $user = temporaryPermissionUserWithRole(Roles::USER);
    $company = Company::factory()->create();
    $employee = Employee::factory()->create([
        'company_id' => $company->id,
        'name' => 'Protected Employee',
        'active' => true,
    ]);

    assignTemporaryPermission(
        $user,
        EmployeePermissions::UPDATE,
        '2026-03-17 08:00:00',
        '2026-03-20 18:00:00',
    );

    $this->travelTo(now()->setDate(2026, 3, 18)->setTime(12, 0));

    $this->actingAs($user)
        ->putJson(route('employees.update', $employee), [
            'company_id' => $company->id,
            'name' => 'Updated Through Temporary Permission',
            'email' => 'updated@example.com',
            'active' => true,
        ])
        ->assertOk();
});

it('ignores temporary permissions before they start', function () {
    $user = temporaryPermissionUserWithRole(Roles::USER);
    $company = Company::factory()->create();
    $employee = Employee::factory()->create(['company_id' => $company->id]);

    assignTemporaryPermission(
        $user,
        EmployeePermissions::UPDATE,
        '2026-03-20 08:00:00',
        '2026-03-25 18:00:00',
    );

    $this->travelTo(now()->setDate(2026, 3, 18)->setTime(12, 0));

    $this->actingAs($user)
        ->putJson(route('employees.update', $employee), [
            'company_id' => $company->id,
            'name' => 'Should Stay Forbidden',
            'email' => 'blocked@example.com',
            'active' => true,
        ])
        ->assertForbidden();
});

it('ignores temporary permissions after they end', function () {
    $user = temporaryPermissionUserWithRole(Roles::USER);
    $company = Company::factory()->create();
    $employee = Employee::factory()->create(['company_id' => $company->id]);

    assignTemporaryPermission(
        $user,
        EmployeePermissions::UPDATE,
        '2026-03-10 08:00:00',
        '2026-03-12 18:00:00',
    );

    $this->travelTo(now()->setDate(2026, 3, 18)->setTime(12, 0));

    $this->actingAs($user)
        ->putJson(route('employees.update', $employee), [
            'company_id' => $company->id,
            'name' => 'Should Stay Forbidden',
            'email' => 'blocked@example.com',
            'active' => true,
        ])
        ->assertForbidden();
});

it('keeps permanent Spatie permissions working normally', function () {
    $manager = temporaryPermissionUserWithRole(Roles::MANAGER);
    $company = Company::factory()->create();
    $employee = Employee::factory()->create(['company_id' => $company->id]);

    $this->actingAs($manager)
        ->putJson(route('employees.update', $employee), [
            'company_id' => $company->id,
            'name' => 'Updated By Permanent Permission',
            'email' => 'manager@example.com',
            'active' => true,
        ])
        ->assertOk();
});

it('does not grant unrelated permissions when only one temporary permission is active', function () {
    $user = temporaryPermissionUserWithRole(Roles::USER);
    $company = Company::factory()->create();
    $employee = Employee::factory()->create(['company_id' => $company->id]);

    assignTemporaryPermission(
        $user,
        EmployeePermissions::UPDATE,
        '2026-03-17 08:00:00',
        '2026-03-20 18:00:00',
    );

    $this->travelTo(now()->setDate(2026, 3, 18)->setTime(12, 0));

    $this->actingAs($user)
        ->putJson(route('employees.update', $employee), [
            'company_id' => $company->id,
            'name' => 'Allowed Employee Update',
            'email' => 'allowed@example.com',
            'active' => true,
        ])
        ->assertOk();

    $this->actingAs($user)
        ->putJson(route('companies.update', $company), [
            'name' => 'Blocked Company Update',
            'email' => 'blocked@company.test',
            'phone' => '123456',
            'address' => 'Blocked Street 1',
            'is_active' => true,
        ])
        ->assertForbidden();
});

it('forbids managing temporary permissions without the admin permission set', function () {
    $targetUser = temporaryPermissionUserWithRole(Roles::USER);
    $permission = Permission::findByName(EmployeePermissions::UPDATE, 'web');

    $this->actingAs(temporaryPermissionUserWithRole(Roles::MANAGER))
        ->postJson(route('user-temporary-permissions.store'), [
            'user_id' => $targetUser->id,
            'permission_id' => $permission->id,
            'starts_at' => '2026-03-20T08:00',
            'ends_at' => '2026-03-25T18:00',
        ])
        ->assertForbidden();
});
