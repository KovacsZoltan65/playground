<?php

use App\Models\Company;
use App\Models\User;
use App\Support\Permissions\Roles;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->seed([
        PermissionSeeder::class,
        RoleSeeder::class,
    ]);
});

it('logs company create activity through spatie activitylog', function () {
    $user = User::factory()->create();
    $user->assignRole(Roles::MANAGER);

    $this->actingAs($user)->postJson(route('companies.store'), [
        'name' => 'Acme Industries',
        'email' => 'hello@acme.test',
        'phone' => '+36 30 123 4567',
        'address' => 'Budapest, Main Street 1',
        'is_active' => true,
    ])->assertCreated();

    $activity = Activity::query()
        ->where('subject_type', Company::class)
        ->latest()
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity?->log_name)->toBe('companies');
});

it('logs reported exceptions', function () {
    Route::get('/test-activitylog-exception', function () {
        throw new RuntimeException('Activitylog exception test');
    });

    $this->get('/test-activitylog-exception')->assertStatus(500);

    expect(Activity::query()
        ->where('log_name', 'errors')
        ->where('event', 'exception')
        ->where('description', 'Activitylog exception test')
        ->exists())->toBeTrue();
});

it('logs frontend errors through the backend endpoint', function () {
    $this->postJson(route('frontend-errors.store'), [
        'type' => 'window-error',
        'message' => 'Unhandled frontend error',
        'url' => 'http://localhost/companies',
        'component' => 'CompanyIndex',
        'stack' => 'stack trace',
        'metadata' => [
            'lineno' => 12,
        ],
    ])->assertCreated();

    expect(Activity::query()
        ->where('log_name', 'frontend')
        ->where('event', 'frontend-error')
        ->where('description', 'Unhandled frontend error')
        ->exists())->toBeTrue();
});

it('does not create activity log entries while seeding', function () {
    Activity::query()->delete();

    $this->seed(DatabaseSeeder::class);

    expect(Activity::query()->count())->toBe(0);
});
