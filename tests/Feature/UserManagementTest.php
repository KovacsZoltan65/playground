<?php

use App\Models\User;
use App\Support\Permissions\Roles;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Auth\Notifications\VerifyEmail;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->seed([
        PermissionSeeder::class,
        RoleSeeder::class,
    ]);
});

function userManagerWithRole(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

it('requires authentication for the users page', function () {
    $this->get(route('users.index'))->assertRedirect(route('login'));
});

it('allows authorized users to list users through the json endpoint', function () {
    User::factory()->count(3)->create();

    $response = $this->actingAs(userManagerWithRole(Roles::ADMIN))
        ->getJson(route('users.list'));

    $response
        ->assertOk()
        ->assertJsonPath('meta.total', User::query()->count());
});

it('forbids listing users without permission', function () {
    $this->actingAs(userManagerWithRole(Roles::USER))
        ->getJson(route('users.list'))
        ->assertForbidden();
});

it('lists users without stale cache when user caching is disabled', function () {
    config()->set('cache.enable_users', false);

    User::factory()->create(['name' => 'Alice One']);
    $admin = userManagerWithRole(Roles::ADMIN);

    $this->actingAs($admin)
        ->getJson(route('users.list'))
        ->assertOk()
        ->assertJsonPath('meta.total', User::query()->count());

    User::factory()->create(['name' => 'Bob Two']);

    $this->actingAs($admin)
        ->getJson(route('users.list'))
        ->assertOk()
        ->assertJsonPath('meta.total', User::query()->count());
});

it('returns the same user list result on repeated requests when user caching is enabled', function () {
    config()->set('cache.enable_users', true);

    User::factory()->create(['name' => 'Alice One']);
    $admin = userManagerWithRole(Roles::ADMIN);
    $baselineCount = User::query()->count();

    $this->actingAs($admin)
        ->getJson(route('users.list'))
        ->assertOk()
        ->assertJsonPath('meta.total', $baselineCount);

    User::factory()->create(['name' => 'Bob Two']);

    $this->actingAs($admin)
        ->getJson(route('users.list'))
        ->assertOk()
        ->assertJsonPath('meta.total', $baselineCount);
});

it('filters users by global search', function () {
    User::factory()->create([
        'name' => 'Alice Auditor',
        'email' => 'alice@example.test',
    ]);
    User::factory()->create([
        'name' => 'Bob Reviewer',
        'email' => 'bob@example.test',
    ]);

    $response = $this->actingAs(userManagerWithRole(Roles::ADMIN))
        ->getJson(route('users.list', ['search' => 'Alice']));

    $response
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Alice Auditor');
});

it('filters users by role', function () {
    $managerRole = Role::findByName(Roles::MANAGER, 'web');

    $manager = User::factory()->create(['name' => 'Manager User']);
    $manager->assignRole(Roles::MANAGER);

    $plainUser = User::factory()->create(['name' => 'Plain User']);
    $plainUser->assignRole(Roles::USER);

    $response = $this->actingAs(userManagerWithRole(Roles::ADMIN))
        ->getJson(route('users.list', ['role_id' => $managerRole->id]));

    $response
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Manager User')
        ->assertJsonPath('data.0.role_names.0', Roles::MANAGER);
});

it('shows a single user with role assignments', function () {
    $user = User::factory()->create();
    $user->assignRole(Roles::MANAGER);

    $this->actingAs(userManagerWithRole(Roles::ADMIN))
        ->getJson(route('users.show', $user))
        ->assertOk()
        ->assertJsonPath('data.name', $user->name)
        ->assertJsonPath('data.role_names.0', Roles::MANAGER);
});

it('creates a user and syncs roles', function () {
    Notification::fake();

    $managerRole = Role::findByName(Roles::MANAGER, 'web');
    $hrRole = Role::findByName(Roles::HR, 'web');

    $this->actingAs(userManagerWithRole(Roles::ADMIN))
        ->postJson(route('users.store'), [
            'name' => 'Alice Manager',
            'email' => 'alice.manager@example.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role_ids' => [$managerRole->id, $hrRole->id],
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Alice Manager')
        ->assertJsonPath('data.roles_count', 2);

    $user = User::query()->where('email', 'alice.manager@example.test')->firstOrFail();

    expect(Hash::check('Password123!', $user->password))->toBeTrue();
    expect($user->roles->pluck('name')->sort()->values()->all())->toBe([Roles::HR, Roles::MANAGER]);
    Notification::assertSentTo($user, VerifyEmail::class);
});

it('validates unique email addresses', function () {
    User::factory()->create([
        'email' => 'alice@example.test',
    ]);

    $this->actingAs(userManagerWithRole(Roles::ADMIN))
        ->postJson(route('users.store'), [
            'name' => 'Duplicate User',
            'email' => 'alice@example.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role_ids' => [],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('requires a password when creating a user', function () {
    $this->actingAs(userManagerWithRole(Roles::ADMIN))
        ->postJson(route('users.store'), [
            'name' => 'Passwordless User',
            'email' => 'passwordless@example.test',
            'role_ids' => [],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});

it('updates a user and resyncs roles', function () {
    $user = User::factory()->create([
        'name' => 'Initial User',
        'email' => 'initial@example.test',
    ]);
    $user->assignRole(Roles::USER);
    $managerRole = Role::findByName(Roles::MANAGER, 'web');

    $this->actingAs(userManagerWithRole(Roles::ADMIN))
        ->putJson(route('users.update', $user), [
            'name' => 'Updated User',
            'email' => 'updated@example.test',
            'role_ids' => [$managerRole->id],
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Updated User')
        ->assertJsonPath('data.roles_count', 1)
        ->assertJsonPath('data.role_names.0', Roles::MANAGER);

    $user->refresh();

    expect($user->roles->pluck('name')->values()->all())->toBe([Roles::MANAGER]);
});

it('does not overwrite the password when it is omitted during update', function () {
    $user = User::factory()->create([
        'password' => 'OldPassword123!',
    ]);
    $originalHash = $user->password;

    $this->actingAs(userManagerWithRole(Roles::ADMIN))
        ->putJson(route('users.update', $user), [
            'name' => 'Updated Name',
            'email' => $user->email,
            'role_ids' => [],
        ])
        ->assertOk();

    $user->refresh();

    expect($user->password)->toBe($originalHash);
});

it('updates the password when a new password is provided', function () {
    $user = User::factory()->create([
        'password' => 'OldPassword123!',
    ]);

    $this->actingAs(userManagerWithRole(Roles::ADMIN))
        ->putJson(route('users.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
            'role_ids' => [],
        ])
        ->assertOk();

    $user->refresh();

    expect(Hash::check('NewPassword123!', $user->password))->toBeTrue();
});

it('deletes a user and clears direct permission assignments', function () {
    $permission = Permission::findByName('companies.viewAny', 'web');
    $user = User::factory()->create();
    $user->assignRole(Roles::MANAGER);
    $user->givePermissionTo($permission);

    $this->actingAs(userManagerWithRole(Roles::ADMIN))
        ->deleteJson(route('users.destroy', $user))
        ->assertOk();

    $this->assertDatabaseMissing('users', [
        'id' => $user->id,
    ]);

    $this->assertDatabaseMissing('model_has_roles', [
        'model_type' => User::class,
        'model_id' => $user->id,
    ]);

    $this->assertDatabaseMissing('model_has_permissions', [
        'model_type' => User::class,
        'model_id' => $user->id,
    ]);
});

it('sends a verification email for an unverified user', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    $this->actingAs(userManagerWithRole(Roles::ADMIN))
        ->postJson(route('users.send-verification-email', $user))
        ->assertOk()
        ->assertJsonPath('message', 'Verification email sent successfully.');

    Notification::assertSentTo($user, VerifyEmail::class);
});

it('resends a verification email for an already verified user', function () {
    Notification::fake();

    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $this->actingAs(userManagerWithRole(Roles::ADMIN))
        ->postJson(route('users.send-verification-email', $user))
        ->assertOk()
        ->assertJsonPath('message', 'Verification email resent successfully.');

    Notification::assertSentTo($user, VerifyEmail::class);
});

it('bulk deletes users', function () {
    $first = User::factory()->create();
    $second = User::factory()->create();

    $this->actingAs(userManagerWithRole(Roles::ADMIN))
        ->deleteJson(route('users.bulk-destroy'), [
            'ids' => [$first->id, $second->id],
        ])
        ->assertOk()
        ->assertJsonPath('deleted', 2);

    $this->assertDatabaseMissing('users', ['id' => $first->id]);
    $this->assertDatabaseMissing('users', ['id' => $second->id]);
});

it('invalidates cached user lists after creating a user', function () {
    config()->set('cache.enable_users', true);

    User::factory()->create(['name' => 'Cache Probe Alpha']);
    $admin = userManagerWithRole(Roles::ADMIN);

    $this->actingAs($admin)
        ->getJson(route('users.list', ['search' => 'Cache Probe']))
        ->assertOk()
        ->assertJsonCount(1, 'data');

    $this->actingAs($admin)
        ->postJson(route('users.store'), [
            'name' => 'Cache Probe Beta',
            'email' => 'cache.probe.beta@example.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role_ids' => [],
        ])
        ->assertCreated();

    $this->actingAs($admin)
        ->getJson(route('users.list', ['search' => 'Cache Probe']))
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('invalidates cached user lists after updating a user', function () {
    config()->set('cache.enable_users', true);

    $user = User::factory()->create([
        'name' => 'Auditor User',
        'email' => 'auditor@example.test',
    ]);
    $admin = userManagerWithRole(Roles::ADMIN);

    $this->actingAs($admin)
        ->getJson(route('users.list', ['search' => 'Auditor']))
        ->assertOk()
        ->assertJsonPath('data.0.name', 'Auditor User');

    $this->actingAs($admin)
        ->putJson(route('users.update', $user), [
            'name' => 'Reviewer User',
            'email' => 'reviewer@example.test',
            'role_ids' => [],
        ])
        ->assertOk();

    $this->actingAs($admin)
        ->getJson(route('users.list', ['search' => 'Reviewer']))
        ->assertOk()
        ->assertJsonPath('data.0.name', 'Reviewer User');
});

it('invalidates cached user lists after deleting a user', function () {
    config()->set('cache.enable_users', true);

    $user = User::factory()->create(['name' => 'Cache Probe Alpha']);
    User::factory()->create(['name' => 'Cache Probe Beta']);
    $admin = userManagerWithRole(Roles::ADMIN);

    $this->actingAs($admin)
        ->getJson(route('users.list', ['search' => 'Cache Probe']))
        ->assertOk()
        ->assertJsonCount(2, 'data');

    $this->actingAs($admin)
        ->deleteJson(route('users.destroy', $user))
        ->assertOk();

    $this->actingAs($admin)
        ->getJson(route('users.list', ['search' => 'Cache Probe']))
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('forbids user creation for users without access', function () {
    $this->actingAs(userManagerWithRole(Roles::USER))
        ->postJson(route('users.store'), [
            'name' => 'Forbidden User',
            'email' => 'forbidden@example.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role_ids' => [],
        ])
        ->assertForbidden();
});

it('forbids user deletion for users without access', function () {
    $user = User::factory()->create();

    $this->actingAs(userManagerWithRole(Roles::USER))
        ->deleteJson(route('users.destroy', $user))
        ->assertForbidden();
});

it('forbids sending verification emails without access', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    $this->actingAs(userManagerWithRole(Roles::USER))
        ->postJson(route('users.send-verification-email', $user))
        ->assertForbidden();

    Notification::assertNothingSent();
});
