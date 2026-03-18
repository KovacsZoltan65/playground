<?php

use App\Models\User;
use App\Support\Permissions\Roles;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->seed([
        PermissionSeeder::class,
        RoleSeeder::class,
    ]);
});

function roleUserWithRole(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

it('requires authentication for the roles page', function () {
    $this->get(route('roles.index'))->assertRedirect(route('login'));
});

it('allows authorized users to list roles through the json endpoint', function () {
    Role::create(['name' => 'Auditor', 'guard_name' => 'web']);

    $response = $this->actingAs(roleUserWithRole(Roles::ADMIN))
        ->getJson(route('roles.list'));

    $response
        ->assertOk()
        ->assertJsonPath('meta.total', Role::query()->count());
});

it('forbids listing roles without the required permission', function () {
    $this->actingAs(roleUserWithRole(Roles::USER))
        ->getJson(route('roles.list'))
        ->assertForbidden();
});

it('lists roles without stale cache when role caching is disabled', function () {
    config()->set('cache.enable_roles', false);

    Role::create(['name' => 'Auditor', 'guard_name' => 'web']);
    $user = roleUserWithRole(Roles::ADMIN);

    $this->actingAs($user)
        ->getJson(route('roles.list'))
        ->assertOk()
        ->assertJsonPath('meta.total', Role::query()->count());

    Role::create(['name' => 'Reviewer', 'guard_name' => 'web']);

    $this->actingAs($user)
        ->getJson(route('roles.list'))
        ->assertOk()
        ->assertJsonPath('meta.total', Role::query()->count());
});

it('returns the same role list result on repeated requests when role caching is enabled', function () {
    config()->set('cache.enable_roles', true);

    Role::create(['name' => 'Auditor', 'guard_name' => 'web']);
    $user = roleUserWithRole(Roles::ADMIN);
    $baselineCount = Role::query()->count();

    $this->actingAs($user)
        ->getJson(route('roles.list'))
        ->assertOk()
        ->assertJsonPath('meta.total', $baselineCount);

    Role::create(['name' => 'Reviewer', 'guard_name' => 'web']);

    $this->actingAs($user)
        ->getJson(route('roles.list'))
        ->assertOk()
        ->assertJsonPath('meta.total', $baselineCount);
});

it('filters roles by global search', function () {
    Role::create(['name' => 'Auditor', 'guard_name' => 'web']);
    Role::create(['name' => 'Reviewer', 'guard_name' => 'web']);

    $response = $this->actingAs(roleUserWithRole(Roles::ADMIN))
        ->getJson(route('roles.list', ['search' => 'Audit']));

    $response
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Auditor');
});

it('filters roles by guard', function () {
    Role::create(['name' => 'Auditor', 'guard_name' => 'web']);
    Role::create(['name' => 'Api Auditor', 'guard_name' => 'api']);

    $response = $this->actingAs(roleUserWithRole(Roles::ADMIN))
        ->getJson(route('roles.list', ['guard_name' => 'api']));

    $response
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Api Auditor')
        ->assertJsonPath('data.0.guard_name', 'api');
});

it('sorts roles by permission count descending', function () {
    $permissionA = Permission::create(['name' => 'reports.view', 'guard_name' => 'web']);
    $permissionB = Permission::create(['name' => 'reports.export', 'guard_name' => 'web']);

    $roleWithoutPermissions = Role::create(['name' => 'Sortable Viewer', 'guard_name' => 'web']);
    $roleWithPermissions = Role::create(['name' => 'Sortable Manager', 'guard_name' => 'web']);
    $roleWithPermissions->syncPermissions([$permissionA->id, $permissionB->id]);

    $response = $this->actingAs(roleUserWithRole(Roles::ADMIN))
        ->getJson(route('roles.list', [
            'search' => 'Sortable',
            'sort_field' => 'permissions_count',
            'sort_direction' => 'desc',
        ]));

    $response
        ->assertOk()
        ->assertJsonPath('data.0.name', 'Sortable Manager')
        ->assertJsonPath('data.1.name', 'Sortable Viewer');
});

it('includes assigned permission information in the role payload', function () {
    $permissionA = Permission::create(['name' => 'reports.view', 'guard_name' => 'web']);
    $permissionB = Permission::create(['name' => 'reports.export', 'guard_name' => 'web']);
    $role = Role::create(['name' => 'Auditor', 'guard_name' => 'web']);
    $role->syncPermissions([$permissionA->id, $permissionB->id]);

    $response = $this->actingAs(roleUserWithRole(Roles::ADMIN))
        ->getJson(route('roles.list', ['search' => 'Auditor']));

    $response
        ->assertOk()
        ->assertJsonPath('data.0.name', 'Auditor')
        ->assertJsonPath('data.0.permissions_count', 2)
        ->assertJsonPath('data.0.permission_names.0', 'reports.view')
        ->assertJsonPath('data.0.permission_names.1', 'reports.export');
});

it('shows a single role', function () {
    $permission = Permission::create(['name' => 'reports.view', 'guard_name' => 'web']);
    $role = Role::create(['name' => 'Auditor', 'guard_name' => 'web']);
    $role->givePermissionTo($permission);

    $this->actingAs(roleUserWithRole(Roles::ADMIN))
        ->getJson(route('roles.show', $role))
        ->assertOk()
        ->assertJsonPath('data.name', 'Auditor')
        ->assertJsonPath('data.permission_names.0', 'reports.view');
});

it('creates a role and syncs permissions', function () {
    $permissionA = Permission::create(['name' => 'reports.view', 'guard_name' => 'web']);
    $permissionB = Permission::create(['name' => 'reports.export', 'guard_name' => 'web']);

    $this->actingAs(roleUserWithRole(Roles::ADMIN))
        ->postJson(route('roles.store'), [
            'name' => 'Auditor',
            'guard_name' => 'web',
            'permission_ids' => [$permissionA->id, $permissionB->id],
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Auditor')
        ->assertJsonPath('data.permissions_count', 2);

    $role = Role::findByName('Auditor');

    expect($role->permissions->pluck('name')->sort()->values()->all())
        ->toBe(['reports.export', 'reports.view']);
});

it('validates unique role names within the same guard', function () {
    Role::create(['name' => 'Auditor', 'guard_name' => 'web']);

    $this->actingAs(roleUserWithRole(Roles::ADMIN))
        ->postJson(route('roles.store'), [
            'name' => 'Auditor',
            'guard_name' => 'web',
            'permission_ids' => [],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});

it('allows the same role name under another guard', function () {
    Role::create(['name' => 'Auditor', 'guard_name' => 'web']);

    $this->actingAs(roleUserWithRole(Roles::ADMIN))
        ->postJson(route('roles.store'), [
            'name' => 'Auditor',
            'guard_name' => 'api',
            'permission_ids' => [],
        ])
        ->assertCreated();
});

it('updates a role and resyncs permissions', function () {
    $permissionA = Permission::create(['name' => 'reports.view', 'guard_name' => 'web']);
    $permissionB = Permission::create(['name' => 'reports.export', 'guard_name' => 'web']);
    $permissionC = Permission::create(['name' => 'reports.delete', 'guard_name' => 'web']);
    $role = Role::create(['name' => 'Auditor', 'guard_name' => 'web']);
    $role->syncPermissions([$permissionA->id, $permissionB->id]);

    $this->actingAs(roleUserWithRole(Roles::ADMIN))
        ->putJson(route('roles.update', $role), [
            'name' => 'Senior Auditor',
            'guard_name' => 'web',
            'permission_ids' => [$permissionC->id],
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Senior Auditor')
        ->assertJsonPath('data.permissions_count', 1)
        ->assertJsonPath('data.permission_names.0', 'reports.delete');

    $role->refresh();

    expect($role->permissions->pluck('name')->values()->all())
        ->toBe(['reports.delete']);
});

it('deletes a role', function () {
    $role = Role::create(['name' => 'Auditor', 'guard_name' => 'web']);

    $this->actingAs(roleUserWithRole(Roles::ADMIN))
        ->deleteJson(route('roles.destroy', $role))
        ->assertOk();

    $this->assertDatabaseMissing('roles', [
        'id' => $role->id,
    ]);
});

it('bulk deletes roles', function () {
    $first = Role::create(['name' => 'Auditor', 'guard_name' => 'web']);
    $second = Role::create(['name' => 'Reviewer', 'guard_name' => 'web']);

    $this->actingAs(roleUserWithRole(Roles::ADMIN))
        ->deleteJson(route('roles.bulk-destroy'), [
            'ids' => [$first->id, $second->id],
        ])
        ->assertOk()
        ->assertJsonPath('deleted', 2);

    $this->assertDatabaseMissing('roles', ['id' => $first->id]);
    $this->assertDatabaseMissing('roles', ['id' => $second->id]);
});

it('invalidates cached role lists after creating a role', function () {
    config()->set('cache.enable_roles', true);

    Role::create(['name' => 'Cache Probe Alpha', 'guard_name' => 'web']);
    $user = roleUserWithRole(Roles::ADMIN);

    $this->actingAs($user)
        ->getJson(route('roles.list', ['search' => 'Cache Probe']))
        ->assertOk()
        ->assertJsonCount(1, 'data');

    $this->actingAs($user)
        ->postJson(route('roles.store'), [
            'name' => 'Cache Probe Beta',
            'guard_name' => 'web',
            'permission_ids' => [],
        ])
        ->assertCreated();

    $this->actingAs($user)
        ->getJson(route('roles.list', ['search' => 'Cache Probe']))
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('invalidates cached role lists after updating a role', function () {
    config()->set('cache.enable_roles', true);

    $role = Role::create(['name' => 'Auditor', 'guard_name' => 'web']);
    $user = roleUserWithRole(Roles::ADMIN);

    $this->actingAs($user)
        ->getJson(route('roles.list', ['search' => 'Audit']))
        ->assertOk()
        ->assertJsonPath('data.0.name', 'Auditor');

    $this->actingAs($user)
        ->putJson(route('roles.update', $role), [
            'name' => 'Reviewer',
            'guard_name' => 'web',
            'permission_ids' => [],
        ])
        ->assertOk();

    $this->actingAs($user)
        ->getJson(route('roles.list', ['search' => 'Review']))
        ->assertOk()
        ->assertJsonPath('data.0.name', 'Reviewer');
});

it('invalidates cached role lists after deleting a role', function () {
    config()->set('cache.enable_roles', true);

    $role = Role::create(['name' => 'Cache Probe Alpha', 'guard_name' => 'web']);
    Role::create(['name' => 'Cache Probe Beta', 'guard_name' => 'web']);
    $user = roleUserWithRole(Roles::ADMIN);

    $this->actingAs($user)
        ->getJson(route('roles.list', ['search' => 'Cache Probe']))
        ->assertOk()
        ->assertJsonCount(2, 'data');

    $this->actingAs($user)
        ->deleteJson(route('roles.destroy', $role))
        ->assertOk();

    $this->actingAs($user)
        ->getJson(route('roles.list', ['search' => 'Cache Probe']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Cache Probe Beta');
});

it('forbids role creation for users without access', function () {
    $this->actingAs(roleUserWithRole(Roles::USER))
        ->postJson(route('roles.store'), [
            'name' => 'Auditor',
            'guard_name' => 'web',
            'permission_ids' => [],
        ])
        ->assertForbidden();
});

it('forbids role deletion for users without access', function () {
    $role = Role::create(['name' => 'Auditor', 'guard_name' => 'web']);

    $this->actingAs(roleUserWithRole(Roles::USER))
        ->deleteJson(route('roles.destroy', $role))
        ->assertForbidden();
});
