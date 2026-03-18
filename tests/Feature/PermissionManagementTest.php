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

function permissionUserWithRole(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

it('requires authentication for the permissions page', function () {
    $this->get(route('permissions.index'))->assertRedirect(route('login'));
});

it('allows authorized users to list permissions through the json endpoint', function () {
    Permission::create(['name' => 'reports.view', 'guard_name' => 'web']);
    Permission::create(['name' => 'reports.export', 'guard_name' => 'web']);

    $response = $this->actingAs(permissionUserWithRole(Roles::ADMIN))
        ->getJson(route('permissions.list'));

    $response
        ->assertOk()
        ->assertJsonPath('meta.total', Permission::query()->count());
});

it('forbids listing permissions without the required permission', function () {
    $this->actingAs(permissionUserWithRole(Roles::USER))
        ->getJson(route('permissions.list'))
        ->assertForbidden();
});

it('lists permissions without stale cache when permission caching is disabled', function () {
    config()->set('cache.enable_permissions', false);

    Permission::create(['name' => 'reports.view', 'guard_name' => 'web']);

    $user = permissionUserWithRole(Roles::ADMIN);

    $this->actingAs($user)
        ->getJson(route('permissions.list'))
        ->assertOk()
        ->assertJsonPath('meta.total', Permission::query()->count());

    Permission::create(['name' => 'reports.export', 'guard_name' => 'web']);

    $this->actingAs($user)
        ->getJson(route('permissions.list'))
        ->assertOk()
        ->assertJsonPath('meta.total', Permission::query()->count());
});

it('returns the same permission list result on repeated requests when permission caching is enabled', function () {
    config()->set('cache.enable_permissions', true);

    Permission::create(['name' => 'reports.view', 'guard_name' => 'web']);

    $user = permissionUserWithRole(Roles::ADMIN);
    $baselineCount = Permission::query()->count();

    $this->actingAs($user)
        ->getJson(route('permissions.list'))
        ->assertOk()
        ->assertJsonPath('meta.total', $baselineCount);

    Permission::create(['name' => 'reports.export', 'guard_name' => 'web']);

    $this->actingAs($user)
        ->getJson(route('permissions.list'))
        ->assertOk()
        ->assertJsonPath('meta.total', $baselineCount);
});

it('filters permissions by global search', function () {
    Permission::create(['name' => 'reports.view', 'guard_name' => 'web']);
    Permission::create(['name' => 'reports.export', 'guard_name' => 'web']);

    $response = $this->actingAs(permissionUserWithRole(Roles::ADMIN))
        ->getJson(route('permissions.list', ['search' => 'export']));

    $response
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'reports.export');
});

it('filters permissions by guard', function () {
    Permission::create(['name' => 'reports.view', 'guard_name' => 'web']);
    Permission::create(['name' => 'api.tokens.read', 'guard_name' => 'api']);

    $response = $this->actingAs(permissionUserWithRole(Roles::ADMIN))
        ->getJson(route('permissions.list', ['guard_name' => 'api']));

    $response
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'api.tokens.read')
        ->assertJsonPath('data.0.guard_name', 'api');
});

it('sorts permissions by assigned role count descending', function () {
    $permissionWithRoles = Permission::create(['name' => 'sortable.reports.view', 'guard_name' => 'web']);
    $permissionWithoutRoles = Permission::create(['name' => 'sortable.reports.export', 'guard_name' => 'web']);

    $managerRole = Role::findByName(Roles::MANAGER);
    $hrRole = Role::findByName(Roles::HR);

    $managerRole->givePermissionTo($permissionWithRoles);
    $hrRole->givePermissionTo($permissionWithRoles);

    $response = $this->actingAs(permissionUserWithRole(Roles::ADMIN))
        ->getJson(route('permissions.list', [
            'search' => 'sortable.reports',
            'sort_field' => 'roles_count',
            'sort_direction' => 'desc',
        ]));

    $response
        ->assertOk()
        ->assertJsonPath('data.0.name', 'sortable.reports.view')
        ->assertJsonPath('data.1.name', 'sortable.reports.export');
});

it('shows how many roles use each permission', function () {
    $permission = Permission::create(['name' => 'reports.view', 'guard_name' => 'web']);
    $otherPermission = Permission::create(['name' => 'reports.export', 'guard_name' => 'web']);

    $managerRole = Role::findByName(Roles::MANAGER);
    $hrRole = Role::findByName(Roles::HR);

    $managerRole->givePermissionTo($permission);
    $hrRole->givePermissionTo($permission);
    $hrRole->givePermissionTo($otherPermission);

    $response = $this->actingAs(permissionUserWithRole(Roles::ADMIN))
        ->getJson(route('permissions.list', ['search' => 'reports.']));

    $response
        ->assertOk()
        ->assertJsonPath('data.0.name', 'reports.export')
        ->assertJsonPath('data.0.roles_count', 1)
        ->assertJsonPath('data.1.name', 'reports.view')
        ->assertJsonPath('data.1.roles_count', 2);
});

it('shows a single permission', function () {
    $permission = Permission::create(['name' => 'reports.view', 'guard_name' => 'web']);

    $this->actingAs(permissionUserWithRole(Roles::ADMIN))
        ->getJson(route('permissions.show', $permission))
        ->assertOk()
        ->assertJsonPath('data.name', 'reports.view')
        ->assertJsonPath('data.guard_name', 'web');
});

it('creates a permission', function () {
    $this->actingAs(permissionUserWithRole(Roles::ADMIN))
        ->postJson(route('permissions.store'), [
            'name' => 'reports.view',
            'guard_name' => 'web',
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'reports.view');

    $this->assertDatabaseHas('permissions', [
        'name' => 'reports.view',
        'guard_name' => 'web',
    ]);
});

it('validates unique permission names within the same guard', function () {
    Permission::create(['name' => 'reports.view', 'guard_name' => 'web']);

    $this->actingAs(permissionUserWithRole(Roles::ADMIN))
        ->postJson(route('permissions.store'), [
            'name' => 'reports.view',
            'guard_name' => 'web',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});

it('allows the same permission name under another guard', function () {
    Permission::create(['name' => 'reports.view', 'guard_name' => 'web']);

    $this->actingAs(permissionUserWithRole(Roles::ADMIN))
        ->postJson(route('permissions.store'), [
            'name' => 'reports.view',
            'guard_name' => 'api',
        ])
        ->assertCreated();
});

it('updates a permission', function () {
    $permission = Permission::create(['name' => 'reports.view', 'guard_name' => 'web']);

    $this->actingAs(permissionUserWithRole(Roles::ADMIN))
        ->putJson(route('permissions.update', $permission), [
            'name' => 'reports.export',
            'guard_name' => 'web',
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'reports.export');

    $this->assertDatabaseHas('permissions', [
        'id' => $permission->id,
        'name' => 'reports.export',
    ]);
});

it('deletes a permission that is assigned to a role', function () {
    $permission = Permission::create(['name' => 'reports.view', 'guard_name' => 'web']);
    Role::findByName(Roles::MANAGER)->givePermissionTo($permission);

    $this->actingAs(permissionUserWithRole(Roles::ADMIN))
        ->deleteJson(route('permissions.destroy', $permission))
        ->assertOk();

    $this->assertDatabaseMissing('permissions', [
        'id' => $permission->id,
    ]);
});

it('bulk deletes permissions', function () {
    $first = Permission::create(['name' => 'reports.view', 'guard_name' => 'web']);
    $second = Permission::create(['name' => 'reports.export', 'guard_name' => 'web']);

    $this->actingAs(permissionUserWithRole(Roles::ADMIN))
        ->deleteJson(route('permissions.bulk-destroy'), [
            'ids' => [$first->id, $second->id],
        ])
        ->assertOk()
        ->assertJsonPath('deleted', 2);

    $this->assertDatabaseMissing('permissions', ['id' => $first->id]);
    $this->assertDatabaseMissing('permissions', ['id' => $second->id]);
});

it('invalidates cached permission lists after creating a permission', function () {
    config()->set('cache.enable_permissions', true);

    Permission::create(['name' => 'reports.view', 'guard_name' => 'web']);
    $user = permissionUserWithRole(Roles::ADMIN);

    $this->actingAs($user)
        ->getJson(route('permissions.list', ['search' => 'reports.']))
        ->assertOk()
        ->assertJsonCount(1, 'data');

    $this->actingAs($user)
        ->postJson(route('permissions.store'), [
            'name' => 'reports.export',
            'guard_name' => 'web',
        ])
        ->assertCreated();

    $this->actingAs($user)
        ->getJson(route('permissions.list', ['search' => 'reports.']))
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('invalidates cached permission lists after updating a permission', function () {
    config()->set('cache.enable_permissions', true);

    $permission = Permission::create(['name' => 'reports.view', 'guard_name' => 'web']);
    $user = permissionUserWithRole(Roles::ADMIN);

    $this->actingAs($user)
        ->getJson(route('permissions.list', ['search' => 'reports.']))
        ->assertOk()
        ->assertJsonPath('data.0.name', 'reports.view');

    $this->actingAs($user)
        ->putJson(route('permissions.update', $permission), [
            'name' => 'reports.export',
            'guard_name' => 'web',
        ])
        ->assertOk();

    $this->actingAs($user)
        ->getJson(route('permissions.list', ['search' => 'reports.']))
        ->assertOk()
        ->assertJsonPath('data.0.name', 'reports.export');
});

it('invalidates cached permission lists after deleting a permission', function () {
    config()->set('cache.enable_permissions', true);

    $permission = Permission::create(['name' => 'reports.view', 'guard_name' => 'web']);
    Permission::create(['name' => 'reports.export', 'guard_name' => 'web']);
    $user = permissionUserWithRole(Roles::ADMIN);

    $this->actingAs($user)
        ->getJson(route('permissions.list', ['search' => 'reports.']))
        ->assertOk()
        ->assertJsonCount(2, 'data');

    $this->actingAs($user)
        ->deleteJson(route('permissions.destroy', $permission))
        ->assertOk();

    $this->actingAs($user)
        ->getJson(route('permissions.list', ['search' => 'reports.']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'reports.export');
});

it('forbids permission creation for users without access', function () {
    $this->actingAs(permissionUserWithRole(Roles::USER))
        ->postJson(route('permissions.store'), [
            'name' => 'reports.view',
            'guard_name' => 'web',
        ])
        ->assertForbidden();
});

it('forbids permission deletion for users without access', function () {
    $permission = Permission::create(['name' => 'reports.view', 'guard_name' => 'web']);

    $this->actingAs(permissionUserWithRole(Roles::USER))
        ->deleteJson(route('permissions.destroy', $permission))
        ->assertForbidden();
});
