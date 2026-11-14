<?php

use App\Models\CatalogSetting;
use App\Models\User;
use App\Services\LabCatalog;
use Database\Seeders\AdminCatalogSeeder;
use Illuminate\Support\Facades\App;

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
        ->assertSee(route('labs.state-transition.index'));

    $this->actingAs($learner)
        ->get(route('labs.state-transition.index'))
        ->assertOk()
        ->assertSee('Theo dõi tiến độ phiếu hỗ trợ');
});

test('a disabled catalog blocks every lab menu entry and direct URL', function () {
    $learner = User::factory()->create();

    $this->actingAs($learner)
        ->get('/home')
        ->assertOk()
        ->assertDontSee('state-transition');

    $this->actingAs($learner)
        ->get(route('labs.state-transition.index'))
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
        ->get(route('labs.state-transition.index'))
        ->assertNotFound();
});

test('an unknown lab key is rejected', function () {
    CatalogSetting::query()->where('key', CatalogSetting::CATALOG_KEY)->update(['enabled' => true]);

    $this->actingAs(User::factory()->create())
        ->get('/catalog/labs/does-not-exist')
        ->assertNotFound();
});

test('the inventory lab is exposed and reachable when enabled', function () {
    CatalogSetting::query()->where('key', CatalogSetting::CATALOG_KEY)->update(['enabled' => true]);

    $learner = User::factory()->create();

    $this->actingAs($learner)
        ->get('/home')
        ->assertOk()
        ->assertSee('inventory-capacity');

    $this->actingAs($learner)
        ->get('/catalog/labs/inventory-capacity')
        ->assertOk();

    $this->actingAs($learner)
        ->get(route('labs.inventory-capacity.index'))
        ->assertOk()
        ->assertSee('Giữ đơn vị cuối mà không bán vượt tồn');
});

test('the business calendar lab is exposed and reachable when enabled', function () {
    CatalogSetting::query()->where('key', CatalogSetting::CATALOG_KEY)->update(['enabled' => true]);

    $learner = User::factory()->create();

    $this->actingAs($learner)
        ->get('/home')
        ->assertOk()
        ->assertSee('business-calendar-slots');

    $this->actingAs($learner)
        ->get(route('labs.business-calendar-slots.index'))
        ->assertOk()
        ->assertSee(__('business_calendar_slots.page.heading'));
});

test('the booking overlap lab is exposed and reachable when enabled', function () {
    CatalogSetting::query()->where('key', CatalogSetting::CATALOG_KEY)->update(['enabled' => true]);
    $learner = User::factory()->create();

    $this->actingAs($learner)
        ->get('/home')
        ->assertOk()
        ->assertSee('booking-overlap');

    $this->actingAs($learner)
        ->get(route('labs.booking-overlap.index'))
        ->assertOk()
        ->assertSee(__('booking_overlap.slots.title'));
});

test('catalog metadata follows the active application locale', function () {
    CatalogSetting::query()->where('key', CatalogSetting::CATALOG_KEY)->update(['enabled' => true]);
    $learner = User::factory()->create();
    $defaultLocale = App::getLocale();

    try {
        foreach (['vi', 'en'] as $locale) {
            App::setLocale($locale);

            $response = $this->actingAs($learner)->get('/home')->assertOk();
            $definition = app(LabCatalog::class)->find('state-transition');

            expect($definition)->not->toBeNull();

            if ($locale === 'vi') {
                $response->assertSee('Học cách bảo vệ trạng thái hợp lệ khi một workflow thay đổi.');
                expect(__($definition->questionKey))
                    ->toBe('Rule nào ngăn một ticket chuyển sang trạng thái không hợp lệ?');
            } else {
                $response->assertSee('Learn how to protect valid state changes in a workflow.');
                $response->assertDontSee('Học cách bảo vệ trạng thái hợp lệ khi một workflow thay đổi.');
                expect(__($definition->questionKey))
                    ->toBe('What rule prevents a ticket from moving to an invalid state?');
            }
        }
    } finally {
        App::setLocale($defaultLocale);
    }
});
