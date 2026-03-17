<?php

use App\Models\SidebarTipPage;
use App\Models\User;
use App\Support\Permissions\Roles;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
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

function usageTipUserWithRole(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

it('requires authentication for the usage tips page', function () {
    $this->get(route('usage-tips.index'))->assertRedirect(route('login'));
});

it('allows privileged users to list usage tip page configurations', function () {
    SidebarTipPage::factory()->count(2)->create();

    $response = $this->actingAs(usageTipUserWithRole(Roles::MANAGER))
        ->getJson(route('usage-tips.list'));

    $response
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('lists usage tip page configurations without stale cache when caching is disabled', function () {
    config()->set('cache.enable_sidebar_tip_pages', false);

    SidebarTipPage::factory()->create([
        'page_component' => 'Dashboard',
    ]);

    $user = usageTipUserWithRole(Roles::MANAGER);

    $this->actingAs($user)
        ->getJson(route('usage-tips.list'))
        ->assertOk()
        ->assertJsonCount(1, 'data');

    SidebarTipPage::factory()->create([
        'page_component' => 'Company/Index',
    ]);

    $this->actingAs($user)
        ->getJson(route('usage-tips.list'))
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('returns the same usage tip page list result on repeated requests when caching is enabled', function () {
    config()->set('cache.enable_sidebar_tip_pages', true);

    SidebarTipPage::factory()->create([
        'page_component' => 'Dashboard',
    ]);

    $user = usageTipUserWithRole(Roles::MANAGER);

    $this->actingAs($user)
        ->getJson(route('usage-tips.list'))
        ->assertOk()
        ->assertJsonCount(1, 'data');

    SidebarTipPage::factory()->create([
        'page_component' => 'Company/Index',
    ]);

    $this->actingAs($user)
        ->getJson(route('usage-tips.list'))
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('invalidates cached usage tip page lists after creating a page', function () {
    config()->set('cache.enable_sidebar_tip_pages', true);

    SidebarTipPage::factory()->create([
        'page_component' => 'Dashboard',
    ]);

    $user = usageTipUserWithRole(Roles::MANAGER);

    $this->actingAs($user)
        ->getJson(route('usage-tips.list'))
        ->assertOk()
        ->assertJsonCount(1, 'data');

    $this->actingAs($user)
        ->postJson(route('usage-tips.store'), [
            'page_component' => 'Company/Index',
            'is_visible' => true,
            'rotation_interval_seconds' => 60,
            'tips' => [
                [
                    'content' => 'Company tip',
                    'sort_order' => 1,
                    'is_active' => true,
                ],
            ],
        ])
        ->assertCreated();

    $this->actingAs($user)
        ->getJson(route('usage-tips.list'))
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('invalidates cached usage tip page lists after updating a page', function () {
    config()->set('cache.enable_sidebar_tip_pages', true);

    $sidebarTipPage = SidebarTipPage::factory()->create([
        'page_component' => 'Dashboard',
        'rotation_interval_seconds' => 45,
    ]);
    $tip = $sidebarTipPage->tips()->create([
        'content' => 'Old tip',
        'sort_order' => 1,
        'is_active' => true,
    ]);

    $user = usageTipUserWithRole(Roles::MANAGER);

    $this->actingAs($user)
        ->getJson(route('usage-tips.list'))
        ->assertOk()
        ->assertJsonPath('data.0.rotation_interval_seconds', 45);

    $this->actingAs($user)
        ->putJson(route('usage-tips.update', $sidebarTipPage), [
            'page_component' => 'Dashboard',
            'is_visible' => true,
            'rotation_interval_seconds' => 30,
            'tips' => [
                [
                    'id' => $tip->id,
                    'content' => 'Updated tip',
                    'sort_order' => 1,
                    'is_active' => true,
                ],
            ],
        ])
        ->assertOk();

    $this->actingAs($user)
        ->getJson(route('usage-tips.list'))
        ->assertOk()
        ->assertJsonPath('data.0.rotation_interval_seconds', 30);
});

it('invalidates cached usage tip page lists after deleting a page', function () {
    config()->set('cache.enable_sidebar_tip_pages', true);

    $sidebarTipPage = SidebarTipPage::factory()->create([
        'page_component' => 'Dashboard',
    ]);
    SidebarTipPage::factory()->create([
        'page_component' => 'Company/Index',
    ]);

    $user = usageTipUserWithRole(Roles::ADMIN);

    $this->actingAs($user)
        ->getJson(route('usage-tips.list'))
        ->assertOk()
        ->assertJsonCount(2, 'data');

    $this->actingAs($user)
        ->deleteJson(route('usage-tips.destroy', $sidebarTipPage))
        ->assertOk();

    $this->actingAs($user)
        ->getJson(route('usage-tips.list'))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.page_component', 'Company/Index');
});

it('allows privileged users to create usage tips', function () {
    $response = $this->actingAs(usageTipUserWithRole(Roles::MANAGER))
        ->postJson(route('usage-tips.store'), [
            'page_component' => 'Dashboard',
            'is_visible' => true,
            'rotation_interval_seconds' => 90,
            'tips' => [
                [
                    'content' => 'Review the dashboard cards first.',
                    'sort_order' => 1,
                    'is_active' => true,
                ],
                [
                    'content' => 'Use the activity panel for recent changes.',
                    'sort_order' => 2,
                    'is_active' => true,
                ],
            ],
        ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.page_component', 'Dashboard')
        ->assertJsonPath('data.rotation_interval_seconds', 90);

    $this->assertDatabaseHas('sidebar_tip_pages', [
        'page_component' => 'Dashboard',
        'rotation_interval_seconds' => 90,
    ]);

    $this->assertDatabaseHas('sidebar_tips', [
        'content' => 'Review the dashboard cards first.',
        'sort_order' => 1,
    ]);
});

it('allows privileged users to update usage tips', function () {
    $sidebarTipPage = SidebarTipPage::factory()->create([
        'page_component' => 'Dashboard',
        'rotation_interval_seconds' => 60,
    ]);
    $firstTip = $sidebarTipPage->tips()->create([
        'content' => 'Old dashboard idea',
        'sort_order' => 1,
        'is_active' => true,
    ]);

    $response = $this->actingAs(usageTipUserWithRole(Roles::MANAGER))
        ->putJson(route('usage-tips.update', $sidebarTipPage), [
            'page_component' => 'Dashboard',
            'is_visible' => false,
            'rotation_interval_seconds' => 120,
            'tips' => [
                [
                    'id' => $firstTip->id,
                    'content' => 'Updated dashboard idea',
                    'sort_order' => 2,
                    'is_active' => false,
                ],
                [
                    'content' => 'A new active dashboard idea',
                    'sort_order' => 1,
                    'is_active' => true,
                ],
            ],
        ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.is_visible', false)
        ->assertJsonPath('data.rotation_interval_seconds', 120);

    $this->assertDatabaseHas('sidebar_tip_pages', [
        'id' => $sidebarTipPage->id,
        'is_visible' => false,
        'rotation_interval_seconds' => 120,
    ]);

    $this->assertDatabaseHas('sidebar_tips', [
        'id' => $firstTip->id,
        'content' => 'Updated dashboard idea',
        'sort_order' => 2,
        'is_active' => false,
    ]);
});

it('allows privileged users to delete usage tip pages', function () {
    $sidebarTipPage = SidebarTipPage::factory()->create();
    $sidebarTipPage->tips()->create([
        'content' => 'Temporary tip',
        'sort_order' => 1,
        'is_active' => true,
    ]);

    $this->actingAs(usageTipUserWithRole(Roles::ADMIN))
        ->deleteJson(route('usage-tips.destroy', $sidebarTipPage))
        ->assertOk();

    $this->assertDatabaseMissing('sidebar_tip_pages', [
        'id' => $sidebarTipPage->id,
    ]);
});

it('forbids usage tip management without permission', function () {
    $response = $this->actingAs(usageTipUserWithRole(Roles::USER))
        ->getJson(route('usage-tips.list'));

    $response->assertForbidden();
});

it('shares active sidebar tips with the current inertia page', function () {
    $sidebarTipPage = SidebarTipPage::factory()->create([
        'page_component' => 'Dashboard',
        'is_visible' => true,
        'rotation_interval_seconds' => 45,
    ]);
    $sidebarTipPage->tips()->createMany([
        [
            'content' => 'First dashboard tip',
            'sort_order' => 1,
            'is_active' => true,
        ],
        [
            'content' => 'Second dashboard tip',
            'sort_order' => 2,
            'is_active' => true,
        ],
    ]);

    $this->actingAs(usageTipUserWithRole(Roles::USER))
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('sidebar_tips.visible', true)
            ->where('sidebar_tips.rotationIntervalMs', 45000)
            ->where('sidebar_tips.tips.0', 'First dashboard tip')
            ->where('sidebar_tips.tips.1', 'Second dashboard tip'));
});

it('invalidates cached sidebar tip route resolution after updating usage tips', function () {
    config()->set('cache.enable_sidebar_tip_pages', true);

    $sidebarTipPage = SidebarTipPage::factory()->create([
        'page_component' => 'Dashboard',
        'is_visible' => true,
        'rotation_interval_seconds' => 45,
    ]);

    $tip = $sidebarTipPage->tips()->create([
        'content' => 'Old dashboard tip',
        'sort_order' => 1,
        'is_active' => true,
    ]);

    $user = usageTipUserWithRole(Roles::MANAGER);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('sidebar_tips.tips.0', 'Old dashboard tip'));

    $this->actingAs($user)
        ->putJson(route('usage-tips.update', $sidebarTipPage), [
            'page_component' => 'Dashboard',
            'is_visible' => true,
            'rotation_interval_seconds' => 30,
            'tips' => [
                [
                    'id' => $tip->id,
                    'content' => 'Updated dashboard tip',
                    'sort_order' => 1,
                    'is_active' => true,
                ],
            ],
        ])
        ->assertOk();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('sidebar_tips.rotationIntervalMs', 30000)
            ->where('sidebar_tips.tips.0', 'Updated dashboard tip'));
});

it('returns the same sidebar tip route resolution on repeated requests when caching is enabled', function () {
    config()->set('cache.enable_sidebar_tip_pages', true);

    $sidebarTipPage = SidebarTipPage::factory()->create([
        'page_component' => 'Dashboard',
        'is_visible' => true,
        'rotation_interval_seconds' => 45,
    ]);

    $tip = $sidebarTipPage->tips()->create([
        'content' => 'Original dashboard tip',
        'sort_order' => 1,
        'is_active' => true,
    ]);

    $user = usageTipUserWithRole(Roles::MANAGER);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('sidebar_tips.tips.0', 'Original dashboard tip'));

    $tip->update([
        'content' => 'Updated directly in database',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('sidebar_tips.tips.0', 'Original dashboard tip'));
});
