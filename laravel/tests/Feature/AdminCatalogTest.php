<?php

use App\Models\CatalogSetting;
use App\Models\User;
use Database\Seeders\AdminCatalogSeeder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Hash;

function createAdminUser(): User
{
    $admin = User::factory()->create();
    $admin->forceFill(['role' => User::ROLE_ADMIN])->save();

    return $admin->refresh();
}

beforeEach(function () {
    $this->seed(AdminCatalogSeeder::class);
});

test('a learner cannot access catalog administration', function () {
    $this->actingAs(User::factory()->create())
        ->get('/admin/catalog')
        ->assertForbidden();

    $this->actingAs(User::factory()->create())
        ->patch('/admin/catalog/labs/state-transition', ['enabled' => false])
        ->assertForbidden();
});

test('an admin can toggle the catalog and the learner shell reflects effective state', function () {
    $admin = createAdminUser();

    $this->actingAs($admin)
        ->get('/admin/catalog')
        ->assertOk()
        ->assertSee('Bật hoặc tắt catalog')
        ->assertSee('State transition &amp; invariant', false)
        ->assertSee('Inventory, reservation &amp; capacity', false);

    $this->actingAs($admin)
        ->patch('/admin/catalog', [
            'enabled' => true,
            'reason' => 'Registry đầu tiên đã sẵn sàng để review.',
        ])
        ->assertRedirect('/admin/catalog');

    $this->assertDatabaseHas('catalog_settings', [
        'key' => 'lab.catalog',
        'enabled' => true,
        'last_changed_by' => $admin->id,
        'reason' => 'Registry đầu tiên đã sẵn sàng để review.',
    ]);

    $this->actingAs(User::factory()->create())
        ->get('/home')
        ->assertOk()
        ->assertSee('Catalog đang bật');
});

test('an admin can toggle an implemented lab while the catalog remains enabled', function () {
    $admin = createAdminUser();
    $learner = User::factory()->create();

    $this->actingAs($admin)
        ->patch('/admin/catalog', ['enabled' => true])
        ->assertRedirect('/admin/catalog');

    $this->actingAs($admin)
        ->patch('/admin/catalog/labs/state-transition', [
            'enabled' => false,
            'reason' => 'Evidence cần review lại.',
        ])
        ->assertRedirect('/admin/catalog');

    $this->assertDatabaseHas('catalog_settings', [
        'key' => 'lab.catalog',
        'enabled' => true,
    ]);
    $this->assertDatabaseHas('catalog_settings', [
        'key' => 'state-transition',
        'enabled' => false,
        'last_changed_by' => $admin->id,
        'reason' => 'Evidence cần review lại.',
    ]);

    $this->actingAs($learner)
        ->get('/home')
        ->assertOk()
        ->assertDontSee('state-transition');

    $this->actingAs($learner)
        ->get(route('labs.state-transition.index'))
        ->assertNotFound();
});

test('an admin cannot enable an unknown lab', function () {
    $admin = createAdminUser();

    $this->actingAs($admin)
        ->patch('/admin/catalog/labs/does-not-exist', ['enabled' => true])
        ->assertNotFound();

    $this->assertDatabaseHas('catalog_settings', [
        'key' => 'inventory-capacity',
        'implemented' => true,
    ]);
});

test('the admin catalog seeder is idempotent and leaves the catalog disabled by default', function () {
    $this->seed(AdminCatalogSeeder::class);

    expect(CatalogSetting::query()->where('key', CatalogSetting::CATALOG_KEY)->count())->toBe(1);
    expect(CatalogSetting::query()->whereIn('key', ['state-transition', 'pricing-discounts', 'inventory-capacity', 'business-calendar-slots'])->count())->toBe(4);
    expect(CatalogSetting::query()->where('key', CatalogSetting::CATALOG_KEY)->first()->isEnabled())->toBeFalse();
    expect(CatalogSetting::query()->where('key', CatalogSetting::CATALOG_KEY)->first()->label_key)
        ->toBe('catalog.global.label');
    expect(CatalogSetting::query()->where('key', 'state-transition')->first()->label_key)
        ->toBe('labs.state_transition.label');
    expect(CatalogSetting::query()->where('key', 'pricing-discounts')->first()->label_key)
        ->toBe('labs.pricing_discounts.label');
});

test('the admin catalog seeder can bootstrap an admin from configured credentials', function () {
    config()->set('catalog.admin.email', 'seed-admin@example.com');
    config()->set('catalog.admin.name', 'Seed Admin');
    config()->set('catalog.admin.password', 'seed-password');

    $this->seed(AdminCatalogSeeder::class);

    $admin = User::query()->where('email', 'seed-admin@example.com')->firstOrFail();

    expect($admin->name)->toBe('Seed Admin');
    expect($admin->role)->toBe(User::ROLE_ADMIN);
    expect(Hash::check('seed-password', $admin->password))->toBeTrue();
});

test('global catalog metadata and flash messages follow the active locale', function () {
    $admin = createAdminUser();
    $defaultLocale = App::getLocale();

    try {
        App::setLocale('en');

        $this->actingAs($admin)
            ->get('/admin/catalog')
            ->assertOk()
            ->assertSee('Allow learners to see the business-logic lab catalog.');

        $this->actingAs($admin)
            ->patch('/admin/catalog', ['enabled' => true])
            ->assertRedirect('/admin/catalog')
            ->assertSessionHas('status', 'The catalog state was updated.');
    } finally {
        App::setLocale($defaultLocale);
    }
});
