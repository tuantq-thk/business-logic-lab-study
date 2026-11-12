<?php

use App\Models\CatalogSetting;
use App\Models\User;
use Database\Seeders\AdminCatalogSeeder;

beforeEach(function () {
    $this->seed(AdminCatalogSeeder::class);
});

test('an enabled implemented lab is rendered in the catalog and can be opened', function () {
    CatalogSetting::query()->where('key', CatalogSetting::CATALOG_KEY)->update(['enabled' => true]);
    $learner = User::factory()->create();

    $this->actingAs($learner)
        ->get('/home')
        ->assertOk()
        ->assertSee('state-transition')
        ->assertSee(route('labs.show', 'state-transition'));

    $this->actingAs($learner)
        ->get(route('labs.show', 'state-transition'))
        ->assertOk()
        ->assertSee('State transition &amp; invariant', false);
});

test('a disabled catalog blocks every lab menu entry and direct URL', function () {
    $learner = User::factory()->create();

    $this->actingAs($learner)
        ->get('/home')
        ->assertOk()
        ->assertDontSee('state-transition');

    $this->actingAs($learner)
        ->get(route('labs.show', 'state-transition'))
        ->assertNotFound();
});

test('a disabled lab is hidden and blocked while the catalog remains enabled', function () {
    CatalogSetting::query()->where('key', CatalogSetting::CATALOG_KEY)->update(['enabled' => true]);
    CatalogSetting::query()->where('key', 'state-transition')->update(['enabled' => false]);
    $learner = User::factory()->create();

    $this->actingAs($learner)
        ->get('/home')
        ->assertOk()
        ->assertSee('Catalog đang bật')
        ->assertDontSee('state-transition');

    $this->actingAs($learner)
        ->get(route('labs.show', 'state-transition'))
        ->assertNotFound();
});

test('an unknown lab key is rejected', function () {
    CatalogSetting::query()->where('key', CatalogSetting::CATALOG_KEY)->update(['enabled' => true]);

    $this->actingAs(User::factory()->create())
        ->get('/labs/does-not-exist')
        ->assertNotFound();
});

test('an unimplemented lab is not exposed or reachable', function () {
    CatalogSetting::query()->where('key', CatalogSetting::CATALOG_KEY)->update(['enabled' => true]);

    $this->actingAs(User::factory()->create())
        ->get('/home')
        ->assertOk()
        ->assertDontSee('inventory-capacity');

    $this->actingAs(User::factory()->create())
        ->get('/labs/inventory-capacity')
        ->assertNotFound();
});
