<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use App\Support\Permissions\Roles;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
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

it('lists companies without stale cache when company caching is disabled', function () {
    config()->set('cache.enable_companies', false);

    Company::factory()->create([
        'name' => 'Acme Industries',
    ]);

    $user = userWithRole(Roles::USER);

    $this->actingAs($user)
        ->getJson(route('companies.list'))
        ->assertOk()
        ->assertJsonCount(1, 'data');

    Company::factory()->create([
        'name' => 'Beta Logistics',
    ]);

    $this->actingAs($user)
        ->getJson(route('companies.list'))
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('returns the same company list result on repeated requests when company caching is enabled', function () {
    config()->set('cache.enable_companies', true);

    Company::factory()->create([
        'name' => 'Acme Industries',
    ]);

    $user = userWithRole(Roles::USER);

    $this->actingAs($user)
        ->getJson(route('companies.list'))
        ->assertOk()
        ->assertJsonCount(1, 'data');

    Company::factory()->create([
        'name' => 'Beta Logistics',
    ]);

    $this->actingAs($user)
        ->getJson(route('companies.list'))
        ->assertOk()
        ->assertJsonCount(1, 'data');
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

it('sorts companies by employee count descending', function () {
    $companyWithoutEmployees = Company::factory()->create([
        'name' => 'No Employees Company',
    ]);
    $companyWithEmployees = Company::factory()->create([
        'name' => 'Many Employees Company',
    ]);

    Employee::factory()->count(3)->create([
        'company_id' => $companyWithEmployees->id,
    ]);

    $response = $this->actingAs(userWithRole(Roles::USER))->getJson(route('companies.list', [
        'sort_field' => 'employees_count',
        'sort_direction' => 'desc',
    ]));

    $response
        ->assertOk()
        ->assertJsonPath('data.0.name', 'Many Employees Company')
        ->assertJsonPath('data.1.name', 'No Employees Company');
});

it('includes employee counts in the company index payload', function () {
    $company = Company::factory()->create([
        'name' => 'Acme Industries',
    ]);
    $otherCompany = Company::factory()->create([
        'name' => 'Beta Logistics',
    ]);

    Employee::factory()->count(3)->create([
        'company_id' => $company->id,
    ]);
    Employee::factory()->create([
        'company_id' => $otherCompany->id,
    ]);

    $response = $this->actingAs(userWithRole(Roles::USER))->getJson(route('companies.list'));

    $response
        ->assertOk()
        ->assertJsonPath('data.0.name', 'Acme Industries')
        ->assertJsonPath('data.0.employees_count', 3)
        ->assertJsonPath('data.1.name', 'Beta Logistics')
        ->assertJsonPath('data.1.employees_count', 1);
});

it('invalidates cached company lists after creating a company', function () {
    config()->set('cache.enable_companies', true);

    Company::factory()->create([
        'name' => 'Acme Industries',
    ]);

    $user = userWithRole(Roles::HR);

    $this->actingAs($user)
        ->getJson(route('companies.list'))
        ->assertOk()
        ->assertJsonCount(1, 'data');

    $this->actingAs($user)
        ->postJson(route('companies.store'), [
            'name' => 'Beta Logistics',
            'email' => 'beta@example.test',
            'phone' => '+36 30 000 1111',
            'address' => 'Budapest, Cache street 2',
            'is_active' => true,
        ])
        ->assertCreated();

    $this->actingAs($user)
        ->getJson(route('companies.list'))
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('invalidates cached company lists after updating a company', function () {
    config()->set('cache.enable_companies', true);

    $company = Company::factory()->create([
        'name' => 'Acme Industries',
        'is_active' => true,
    ]);

    $user = userWithRole(Roles::MANAGER);

    $this->actingAs($user)
        ->getJson(route('companies.list'))
        ->assertOk()
        ->assertJsonPath('data.0.name', 'Acme Industries');

    $this->actingAs($user)
        ->putJson(route('companies.update', $company), [
            'name' => 'Updated Company',
            'email' => 'updated@company.test',
            'phone' => '123456',
            'address' => 'Updated Address',
            'is_active' => false,
        ])
        ->assertOk();

    $this->actingAs($user)
        ->getJson(route('companies.list'))
        ->assertOk()
        ->assertJsonPath('data.0.name', 'Updated Company')
        ->assertJsonPath('data.0.is_active', false);
});

it('invalidates cached company lists after deleting a company', function () {
    config()->set('cache.enable_companies', true);

    $company = Company::factory()->create([
        'name' => 'Acme Industries',
    ]);
    Company::factory()->create([
        'name' => 'Beta Logistics',
    ]);

    $user = userWithRole(Roles::MANAGER);

    $this->actingAs($user)
        ->getJson(route('companies.list'))
        ->assertOk()
        ->assertJsonCount(2, 'data');

    $this->actingAs($user)
        ->deleteJson(route('companies.destroy', $company))
        ->assertOk();

    $this->actingAs($user)
        ->getJson(route('companies.list'))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Beta Logistics');
});

it('invalidates cached company lists after toggling a company status', function () {
    config()->set('cache.enable_companies', true);

    $company = Company::factory()->create([
        'name' => 'Acme Industries',
        'is_active' => true,
    ]);

    $user = userWithRole(Roles::MANAGER);

    $this->actingAs($user)
        ->getJson(route('companies.list'))
        ->assertOk()
        ->assertJsonPath('data.0.is_active', true);

    $this->actingAs($user)
        ->patchJson(route('companies.toggle-active', $company))
        ->assertOk();

    $this->actingAs($user)
        ->getJson(route('companies.list'))
        ->assertOk()
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

    $this->assertSoftDeleted('companies', [
        'id' => $company->id,
    ]);
});

it('allows authenticated users to toggle company active status', function () {
    $user = userWithRole(Roles::MANAGER);
    $company = Company::factory()->create([
        'is_active' => true,
    ]);

    $response = $this->actingAs($user)
        ->patchJson(route('companies.toggle-active', $company));

    $response
        ->assertOk()
        ->assertJsonPath('data.is_active', false);

    $this->assertDatabaseHas('companies', [
        'id' => $company->id,
        'is_active' => false,
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
        $this->assertSoftDeleted('companies', [
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

it('forbids toggling company status without permission', function () {
    $company = Company::factory()->create([
        'is_active' => true,
    ]);

    $response = $this->actingAs(User::factory()->create())
        ->patchJson(route('companies.toggle-active', $company));

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
