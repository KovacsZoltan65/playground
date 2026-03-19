<?php

use App\Models\Company;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\seed;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Cache::flush();
    seed(PermissionSeeder::class);
    seed(RoleSeeder::class);
});

it('allows authenticated users to bulk activate companies', function (): void {
    $user = User::factory()->create();
    $user->assignRole('Admin');

    $companies = Company::factory()->count(2)->create([
        'is_active' => false,
    ]);

    actingAs($user);

    patchJson(route('companies.bulk-activate'), [
        'ids' => $companies->pluck('id')->all(),
    ])->assertOk()
        ->assertJsonPath('data.0.is_active', true);

    expect($companies->fresh()->every(fn (Company $company) => $company->is_active))->toBeTrue();
});

it('allows authenticated users to bulk deactivate companies', function (): void {
    $user = User::factory()->create();
    $user->assignRole('Admin');

    $companies = Company::factory()->count(2)->create([
        'is_active' => true,
    ]);

    actingAs($user);

    patchJson(route('companies.bulk-deactivate'), [
        'ids' => $companies->pluck('id')->all(),
    ])->assertOk()
        ->assertJsonPath('data.0.is_active', false);

    expect($companies->fresh()->every(fn (Company $company) => ! $company->is_active))->toBeTrue();
});

it('forbids bulk company status updates without update permission', function (): void {
    $user = User::factory()->create();
    $user->assignRole('User');

    $companies = Company::factory()->count(2)->create([
        'is_active' => false,
    ]);

    actingAs($user);

    patchJson(route('companies.bulk-activate'), [
        'ids' => $companies->pluck('id')->all(),
    ])->assertForbidden();
});

it('invalidates cached company lists after bulk status updates', function (): void {
    config()->set('cache.enable_companies', true);

    $user = User::factory()->create();
    $user->assignRole('Admin');

    $companies = Company::factory()->count(2)->create([
        'is_active' => false,
    ]);

    actingAs($user);

    $this->getJson(route('companies.list'))
        ->assertOk()
        ->assertJsonPath('data.0.is_active', false);

    patchJson(route('companies.bulk-activate'), [
        'ids' => $companies->pluck('id')->all(),
    ])->assertOk();

    $this->getJson(route('companies.list'))
        ->assertOk()
        ->assertJsonPath('data.0.is_active', true);
});
