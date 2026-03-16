<?php

use App\Models\Company;
use App\Models\Employee;
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

function employeeUserWithRole(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

it('requires authentication for the employees page', function () {
    $this->get(route('employees.index'))->assertRedirect(route('login'));
});

it('allows authenticated users to list employees through the json endpoint', function () {
    Employee::factory()->count(3)->create();

    $response = $this->actingAs(employeeUserWithRole(Roles::USER))
        ->getJson(route('employees.list'));

    $response
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

it('filters employees by company and status', function () {
    $company = Company::factory()->create(['name' => 'Acme']);
    $otherCompany = Company::factory()->create(['name' => 'Beta']);

    Employee::factory()->create([
        'company_id' => $company->id,
        'name' => 'Active Match',
        'active' => true,
    ]);

    Employee::factory()->create([
        'company_id' => $otherCompany->id,
        'name' => 'Inactive Miss',
        'active' => false,
    ]);

    $response = $this->actingAs(employeeUserWithRole(Roles::USER))
        ->getJson(route('employees.list', [
            'company_id' => $company->id,
            'active' => 'true',
        ]));

    $response
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Active Match')
        ->assertJsonPath('data.0.company_name', 'Acme');
});

it('sorts employees by name descending', function () {
    Employee::factory()->create(['name' => 'Alice Example']);
    Employee::factory()->create(['name' => 'Zoe Example']);

    $response = $this->actingAs(employeeUserWithRole(Roles::USER))
        ->getJson(route('employees.list', [
            'sort_field' => 'name',
            'sort_direction' => 'desc',
        ]));

    $response
        ->assertOk()
        ->assertJsonPath('data.0.name', 'Zoe Example');
});

it('allows authenticated users to create employees', function () {
    $company = Company::factory()->create();

    $response = $this->actingAs(employeeUserWithRole(Roles::HR))
        ->postJson(route('employees.store'), [
            'company_id' => $company->id,
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'active' => true,
        ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.name', 'Jane Doe')
        ->assertJsonPath('data.company_id', $company->id);

    $this->assertDatabaseHas('employees', [
        'company_id' => $company->id,
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
    ]);
});

it('allows authenticated users to update employees', function () {
    $employee = Employee::factory()->create();
    $newCompany = Company::factory()->create();

    $response = $this->actingAs(employeeUserWithRole(Roles::MANAGER))
        ->putJson(route('employees.update', $employee), [
            'company_id' => $newCompany->id,
            'name' => 'Updated Employee',
            'email' => 'updated.employee@example.com',
            'active' => false,
        ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.name', 'Updated Employee')
        ->assertJsonPath('data.active', false)
        ->assertJsonPath('data.company_id', $newCompany->id);

    $this->assertDatabaseHas('employees', [
        'id' => $employee->id,
        'company_id' => $newCompany->id,
        'active' => false,
    ]);
});

it('allows authenticated users to soft delete employees', function () {
    $employee = Employee::factory()->create();

    $this->actingAs(employeeUserWithRole(Roles::MANAGER))
        ->deleteJson(route('employees.destroy', $employee))
        ->assertOk();

    $this->assertSoftDeleted('employees', [
        'id' => $employee->id,
    ]);
});

it('allows authenticated users to toggle employee active status', function () {
    $employee = Employee::factory()->create([
        'active' => true,
    ]);

    $response = $this->actingAs(employeeUserWithRole(Roles::MANAGER))
        ->patchJson(route('employees.toggle-active', $employee));

    $response
        ->assertOk()
        ->assertJsonPath('data.active', false);

    $this->assertDatabaseHas('employees', [
        'id' => $employee->id,
        'active' => false,
    ]);
});

it('allows authenticated users to bulk delete employees', function () {
    $employees = Employee::factory()->count(3)->create();

    $response = $this->actingAs(employeeUserWithRole(Roles::ADMIN))
        ->deleteJson(route('employees.bulk-destroy'), [
            'ids' => $employees->pluck('id')->all(),
        ]);

    $response
        ->assertOk()
        ->assertJsonPath('deleted', 3);

    foreach ($employees as $employee) {
        $this->assertSoftDeleted('employees', [
            'id' => $employee->id,
        ]);
    }
});

it('allows authenticated users to bulk activate employees', function () {
    $employees = Employee::factory()->count(2)->create([
        'active' => false,
    ]);

    $response = $this->actingAs(employeeUserWithRole(Roles::ADMIN))
        ->patchJson(route('employees.bulk-activate'), [
            'ids' => $employees->pluck('id')->all(),
        ]);

    $response
        ->assertOk()
        ->assertJsonPath('count', 2);

    foreach ($employees as $employee) {
        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'active' => true,
        ]);
    }
});

it('allows authenticated users to bulk deactivate employees', function () {
    $employees = Employee::factory()->count(2)->create([
        'active' => true,
    ]);

    $response = $this->actingAs(employeeUserWithRole(Roles::ADMIN))
        ->patchJson(route('employees.bulk-deactivate'), [
            'ids' => $employees->pluck('id')->all(),
        ]);

    $response
        ->assertOk()
        ->assertJsonPath('count', 2);

    foreach ($employees as $employee) {
        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'active' => false,
        ]);
    }
});

it('forbids listing employees without permission', function () {
    Employee::factory()->count(2)->create();

    $this->actingAs(User::factory()->create())
        ->getJson(route('employees.list'))
        ->assertForbidden();
});

it('forbids creating employees without permission', function () {
    $company = Company::factory()->create();

    $this->actingAs(User::factory()->create())
        ->postJson(route('employees.store'), [
            'company_id' => $company->id,
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'active' => true,
        ])
        ->assertForbidden();
});

it('forbids toggling employee status without permission', function () {
    $employee = Employee::factory()->create();

    $this->actingAs(User::factory()->create())
        ->patchJson(route('employees.toggle-active', $employee))
        ->assertForbidden();
});

it('forbids bulk employee activation without permission', function () {
    $employees = Employee::factory()->count(2)->create([
        'active' => false,
    ]);

    $this->actingAs(User::factory()->create())
        ->patchJson(route('employees.bulk-activate'), [
            'ids' => $employees->pluck('id')->all(),
        ])
        ->assertForbidden();
});

it('forbids bulk employee deactivation without permission', function () {
    $employees = Employee::factory()->count(2)->create([
        'active' => true,
    ]);

    $this->actingAs(User::factory()->create())
        ->patchJson(route('employees.bulk-deactivate'), [
            'ids' => $employees->pluck('id')->all(),
        ])
        ->assertForbidden();
});
