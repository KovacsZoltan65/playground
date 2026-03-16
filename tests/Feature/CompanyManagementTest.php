<?php

use App\Models\Company;
use App\Models\User;
use App\Support\Permissions\Roles;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->seed([
        PermissionSeeder::class,
        RoleSeeder::class,
    ]);
});

function userWithRole(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

it('requires authentication for the companies page', function () {
    $this->get(route('companies.index'))->assertRedirect(route('login'));
});

it('allows authenticated users to list companies through the json endpoint', function () {
    Company::factory()->count(3)->create();

    $user = userWithRole(Roles::USER);

    $response = $this->actingAs($user)->getJson(route('companies.list'));

    $response
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

it('filters companies by the datatable global search', function () {
    Company::factory()->create([
        'name' => 'Acme Industries',
        'email' => 'hello@acme.test',
    ]);
    Company::factory()->create([
        'name' => 'Beta Logistics',
        'email' => 'beta@example.test',
    ]);

    $response = $this->actingAs(userWithRole(Roles::USER))->getJson(route('companies.list', [
        'search' => 'Acme',
    ]));

    $response
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Acme Industries');
});

it('filters companies by datatable status filter', function () {
    Company::factory()->create([
        'name' => 'Active Company',
        'is_active' => true,
    ]);
    Company::factory()->create([
        'name' => 'Inactive Company',
        'is_active' => false,
    ]);

    $response = $this->actingAs(userWithRole(Roles::USER))->getJson(route('companies.list', [
        'is_active' => 'false',
    ]));

    $response
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Inactive Company')
        ->assertJsonPath('data.0.is_active', false);
});

it('allows authenticated users to create companies', function () {
    $user = userWithRole(Roles::HR);

    $response = $this->actingAs($user)->postJson(route('companies.store'), [
        'name' => 'Acme Industries',
        'email' => 'hello@acme.test',
        'phone' => '+36 30 123 4567',
        'address' => 'Budapest, Main Street 1',
        'is_active' => true,
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.name', 'Acme Industries');

    $this->assertDatabaseHas('companies', [
        'name' => 'Acme Industries',
        'email' => 'hello@acme.test',
    ]);
});

it('allows authenticated users to update companies', function () {
    $user = userWithRole(Roles::MANAGER);
    $company = Company::factory()->create();

    $response = $this->actingAs($user)->putJson(route('companies.update', $company), [
        'name' => 'Updated Company',
        'email' => 'updated@company.test',
        'phone' => '123456',
        'address' => 'Updated Address',
        'is_active' => false,
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.name', 'Updated Company')
        ->assertJsonPath('data.is_active', false);

    $this->assertDatabaseHas('companies', [
        'id' => $company->id,
        'name' => 'Updated Company',
        'is_active' => false,
    ]);
});

it('allows authenticated users to delete companies', function () {
    $user = userWithRole(Roles::MANAGER);
    $company = Company::factory()->create();

    $this->actingAs($user)
        ->deleteJson(route('companies.destroy', $company))
        ->assertOk();

    $this->assertDatabaseMissing('companies', [
        'id' => $company->id,
    ]);
});

it('allows authenticated users to bulk delete companies', function () {
    $user = userWithRole(Roles::ADMIN);
    $companies = Company::factory()->count(3)->create();

    $response = $this->actingAs($user)->deleteJson(route('companies.bulk-destroy'), [
        'ids' => $companies->pluck('id')->all(),
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('deleted', 3);

    foreach ($companies as $company) {
        $this->assertDatabaseMissing('companies', [
            'id' => $company->id,
        ]);
    }
});

it('forbids listing companies without permission', function () {
    Company::factory()->count(3)->create();

    $response = $this->actingAs(User::factory()->create())->getJson(route('companies.list'));

    $response->assertForbidden();
});

it('allows users to view companies with the user role', function () {
    Company::factory()->count(3)->create();

    $response = $this->actingAs(userWithRole(Roles::USER))->getJson(route('companies.list'));

    $response
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

it('forbids creating companies without permission', function () {
    $response = $this->actingAs(User::factory()->create())->postJson(route('companies.store'), [
        'name' => 'Acme Industries',
        'email' => 'hello@acme.test',
        'phone' => '+36 30 123 4567',
        'address' => 'Budapest, Main Street 1',
        'is_active' => true,
    ]);

    $response->assertForbidden();
});

it('forbids users from creating companies with the user role', function () {
    $response = $this->actingAs(userWithRole(Roles::USER))->postJson(route('companies.store'), [
        'name' => 'Acme Industries',
        'email' => 'hello@acme.test',
        'phone' => '+36 30 123 4567',
        'address' => 'Budapest, Main Street 1',
        'is_active' => true,
    ]);

    $response->assertForbidden();
});

it('forbids updating companies without permission', function () {
    $company = Company::factory()->create();

    $response = $this->actingAs(User::factory()->create())->putJson(route('companies.update', $company), [
        'name' => 'Updated Company',
        'email' => 'updated@company.test',
        'phone' => '123456',
        'address' => 'Updated Address',
        'is_active' => false,
    ]);

    $response->assertForbidden();
});

it('forbids hr from deleting companies', function () {
    $company = Company::factory()->create();

    $response = $this->actingAs(userWithRole(Roles::HR))->deleteJson(route('companies.destroy', $company));

    $response->assertForbidden();
});

it('forbids deleting companies without permission', function () {
    $company = Company::factory()->create();

    $response = $this->actingAs(User::factory()->create())->deleteJson(route('companies.destroy', $company));

    $response->assertForbidden();
});
