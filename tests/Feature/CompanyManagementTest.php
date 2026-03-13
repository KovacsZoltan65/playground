<?php

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('requires authentication for the companies page', function () {
    $this->get(route('companies.index'))->assertRedirect(route('login'));
});

it('allows authenticated users to list companies through the json endpoint', function () {
    Company::factory()->count(3)->create();

    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson(route('companies.list'));

    $response
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

it('allows authenticated users to create companies', function () {
    $user = User::factory()->create();

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
    $user = User::factory()->create();
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
    $user = User::factory()->create();
    $company = Company::factory()->create();

    $this->actingAs($user)
        ->deleteJson(route('companies.destroy', $company))
        ->assertOk();

    $this->assertDatabaseMissing('companies', [
        'id' => $company->id,
    ]);
});
