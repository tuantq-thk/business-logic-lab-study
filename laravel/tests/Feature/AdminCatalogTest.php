<?php

use App\Models\CatalogSetting;
use App\Models\User;
use Database\Seeders\AdminCatalogSeeder;
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
});

test('an admin can toggle the catalog and the learner shell reflects effective state', function () {
    $admin = createAdminUser();

    $this->actingAs($admin)
        ->get('/admin/catalog')
        ->assertOk()
        ->assertSee('Bật hoặc tắt catalog');

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

test('the admin catalog seeder is idempotent and leaves the catalog disabled by default', function () {
    $this->seed(AdminCatalogSeeder::class);

    expect(CatalogSetting::query()->where('key', CatalogSetting::CATALOG_KEY)->count())->toBe(1);
    expect(CatalogSetting::query()->where('key', CatalogSetting::CATALOG_KEY)->first()->isEnabled())->toBeFalse();
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
