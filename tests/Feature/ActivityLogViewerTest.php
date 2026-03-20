<?php

use App\Models\Company;
use App\Models\User;
use App\Support\Permissions\Roles;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

function activityLogViewerUser(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

it('requires authentication for the activity logs page', function () {
    $this->get(route('activity-logs.index'))->assertRedirect(route('login'));
});

it('allows authorized users to list activity logs through the json endpoint', function () {
    $actor = activityLogViewerUser(Roles::ADMIN);
    $subject = Company::factory()->create(['name' => 'Acme Audit']);
    Activity::query()->delete();

    activity('companies')
        ->causedBy($actor)
        ->performedOn($subject)
        ->event('created')
        ->log('Company created');

    $response = $this->actingAs($actor)
        ->getJson(route('activity-logs.list'));

    $response
        ->assertOk()
        ->assertJsonPath('data.0.log_name', 'companies')
        ->assertJsonPath('data.0.event', 'created')
        ->assertJsonPath('data.0.causer_label', $actor->name)
        ->assertJsonPath('data.0.subject_label', 'Acme Audit');
});

it('returns aggregate analysis for the current activity log filters', function () {
    $actor = activityLogViewerUser(Roles::ADMIN);
    Activity::query()->delete();
    $now = now()->startOfDay();

    Activity::query()->create([
        'log_name' => 'errors',
        'description' => 'Unhandled exception',
        'subject_type' => null,
        'subject_id' => null,
        'causer_type' => User::class,
        'causer_id' => $actor->id,
        'event' => 'exception',
        'properties' => [],
        'created_at' => $now->copy()->subDay(),
        'updated_at' => $now->copy()->subDay(),
    ]);

    Activity::query()->create([
        'log_name' => 'frontend',
        'description' => 'Browser error',
        'subject_type' => null,
        'subject_id' => null,
        'causer_type' => null,
        'causer_id' => null,
        'event' => 'frontend-error',
        'properties' => [],
        'created_at' => $now->copy(),
        'updated_at' => $now->copy(),
    ]);

    $this->actingAs($actor)
        ->getJson(route('activity-logs.analysis'))
        ->assertOk()
        ->assertJsonPath('totals.entries', 2)
        ->assertJsonPath('totals.exceptions', 1)
        ->assertJsonPath('totals.frontend_errors', 1)
        ->assertJsonPath('totals.system_entries', 1)
        ->assertJsonPath('totals.distinct_log_names', 2)
        ->assertJsonPath('event_breakdown.0.count', 1)
        ->assertJsonPath('log_name_breakdown.0.count', 1)
        ->assertJsonPath('daily_activity.0.date', $now->toDateString())
        ->assertJsonPath('daily_activity.1.date', $now->copy()->subDay()->toDateString())
        ->assertJsonPath('daily_activity.6.date', $now->copy()->subDays(6)->toDateString())
        ->assertJsonCount(7, 'daily_activity');
});

it('forbids listing activity logs without permission', function () {
    $this->actingAs(activityLogViewerUser(Roles::USER))
        ->getJson(route('activity-logs.list'))
        ->assertForbidden();
});

it('forbids viewing activity log analysis without permission', function () {
    $this->actingAs(activityLogViewerUser(Roles::USER))
        ->getJson(route('activity-logs.analysis'))
        ->assertForbidden();
});

it('filters activity logs by log name', function () {
    $actor = activityLogViewerUser(Roles::ADMIN);

    activity('companies')
        ->causedBy($actor)
        ->event('created')
        ->log('Company entry');

    activity('frontend')
        ->event('frontend-error')
        ->log('Frontend entry');

    $this->actingAs($actor)
        ->getJson(route('activity-logs.list', ['log_name' => 'frontend']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.log_name', 'frontend');
});

it('filters activity logs by global search', function () {
    $actor = activityLogViewerUser(Roles::ADMIN);

    activity('companies')
        ->causedBy($actor)
        ->event('created')
        ->log('Created company record');

    activity('errors')
        ->event('exception')
        ->log('Unhandled database exception');

    $this->actingAs($actor)
        ->getJson(route('activity-logs.list', ['search' => 'database exception']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.log_name', 'errors')
        ->assertJsonPath('data.0.event', 'exception');
});

it('sorts activity logs by log name ascending', function () {
    $actor = activityLogViewerUser(Roles::ADMIN);
    Activity::query()->delete();

    activity('zeta')
        ->causedBy($actor)
        ->event('created')
        ->log('Zeta entry');

    activity('alpha')
        ->causedBy($actor)
        ->event('created')
        ->log('Alpha entry');

    $this->actingAs($actor)
        ->getJson(route('activity-logs.list', [
            'sort_field' => 'log_name',
            'sort_direction' => 'asc',
        ]))
        ->assertOk()
        ->assertJsonPath('data.0.log_name', 'alpha')
        ->assertJsonPath('data.1.log_name', 'zeta');
});

it('renders the activity logs page for authorized users', function () {
    $this->actingAs(activityLogViewerUser(Roles::ADMIN))
        ->get(route('activity-logs.index'))
        ->assertOk();
});
