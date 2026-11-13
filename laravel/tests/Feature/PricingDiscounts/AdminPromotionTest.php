<?php

use App\Features\PricingDiscounts\Models\PricingPromotion;
use App\Features\PricingDiscounts\Models\PricingQuote;
use App\Models\CatalogSetting;
use App\Models\User;
use Database\Seeders\AdminCatalogSeeder;
use Database\Seeders\PricingDiscountsSeeder;
use Illuminate\Support\Facades\App;

function pricingPromotionAdmin(): User
{
    $admin = User::factory()->create();
    $admin->forceFill(['role' => User::ROLE_ADMIN])->save();

    return $admin->refresh();
}

beforeEach(function () {
    $this->seed(AdminCatalogSeeder::class);
    $this->seed(PricingDiscountsSeeder::class);
    CatalogSetting::query()->where('key', CatalogSetting::CATALOG_KEY)->update(['enabled' => true]);
});

test('a learner cannot manage promotions', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.pricing-promotions.index'))
        ->assertForbidden();

    $this->actingAs(User::factory()->create())
        ->post(route('admin.pricing-promotions.store'), [])
        ->assertForbidden();
});

test('promotion admin UI supports English and Vietnamese', function () {
    $admin = pricingPromotionAdmin();
    $defaultLocale = App::getLocale();

    try {
        App::setLocale('vi');
        $this->followingRedirects()->actingAs($admin)
            ->get(route('admin.pricing-promotions.index'))
            ->assertSee('Quản lý promotion');

        App::setLocale('en');
        $this->followingRedirects()->actingAs($admin)
            ->get(route('admin.pricing-promotions.index'))
            ->assertSee('Manage promotions');
    } finally {
        App::setLocale($defaultLocale);
    }
});

test('an admin can create update and delete a promotion', function () {
    $admin = pricingPromotionAdmin();

    $this->followingRedirects()->actingAs($admin)
        ->get(route('admin.pricing-promotions.index'))
        ->assertOk()
        ->assertSee('WELCOME10')
        ->assertSee('BULK15');

    $this->actingAs($admin)
        ->post(route('admin.pricing-promotions.store'), [
            'code' => 'SPRING20',
            'kind' => 'percentage',
            'value' => 20,
            'minimum_subtotal_minor' => 6000,
            'priority' => 25,
            'stackable' => true,
            'active' => true,
        ])
        ->assertRedirect(route('labs.pricing-discounts.index'));

    $promotion = PricingPromotion::query()->where('code', 'SPRING20')->firstOrFail();
    expect($promotion->value)->toBe(20)
        ->and($promotion->stackable)->toBeTrue();

    $this->actingAs($admin)
        ->patch(route('admin.pricing-promotions.update', $promotion), [
            'code' => 'SPRING25',
            'kind' => 'percentage',
            'value' => 25,
            'minimum_subtotal_minor' => 6000,
            'priority' => 25,
            'stackable' => false,
            'active' => false,
        ])
        ->assertRedirect(route('labs.pricing-discounts.index'));

    expect($promotion->refresh()->code)->toBe('SPRING25')
        ->and($promotion->active)->toBeFalse()
        ->and($promotion->stackable)->toBeFalse();

    $this->actingAs($admin)
        ->delete(route('admin.pricing-promotions.destroy', $promotion))
        ->assertRedirect(route('labs.pricing-discounts.index'));

    expect(PricingPromotion::query()->whereKey($promotion->id)->exists())->toBeFalse();
});

test('promotion validation rejects invalid values, windows and duplicate codes', function () {
    $admin = pricingPromotionAdmin();

    $this->actingAs($admin)
        ->from(route('labs.pricing-discounts.index'))
        ->post(route('admin.pricing-promotions.store'), [
            'code' => 'INVALID_PERCENTAGE',
            'kind' => 'percentage',
            'value' => 101,
            'minimum_subtotal_minor' => 0,
            'priority' => 10,
            'stackable' => false,
            'active' => true,
        ])
        ->assertRedirect(route('labs.pricing-discounts.index'))
        ->assertSessionHasErrors('value');

    $this->actingAs($admin)
        ->from(route('labs.pricing-discounts.index'))
        ->post(route('admin.pricing-promotions.store'), [
            'code' => 'INVALID_WINDOW',
            'kind' => 'fixed',
            'value' => 100,
            'minimum_subtotal_minor' => 0,
            'priority' => 10,
            'stackable' => false,
            'active' => true,
            'starts_at' => '2026-12-31 00:00:00',
            'ends_at' => '2026-12-01 00:00:00',
        ])
        ->assertRedirect(route('labs.pricing-discounts.index'))
        ->assertSessionHasErrors('ends_at');

    $this->actingAs($admin)
        ->from(route('labs.pricing-discounts.index'))
        ->post(route('admin.pricing-promotions.store'), [
            'code' => 'WELCOME10',
            'kind' => 'percentage',
            'value' => 10,
            'minimum_subtotal_minor' => 0,
            'priority' => 10,
            'stackable' => true,
            'active' => true,
        ])
        ->assertRedirect(route('labs.pricing-discounts.index'))
        ->assertSessionHasErrors('code');
});

test('deleting a promotion preserves the quote discount snapshot', function () {
    $admin = pricingPromotionAdmin();
    $promotion = PricingPromotion::query()->where('code', 'VIP700')->firstOrFail();

    $this->actingAs($admin)
        ->post(route('labs.pricing-discounts.quote'), [
            'quantities' => ['book-php' => 1, 'book-sql' => 1],
            'rules' => [$promotion->code],
        ])
        ->assertRedirect();

    $this->actingAs($admin)
        ->delete(route('admin.pricing-promotions.destroy', $promotion))
        ->assertRedirect();

    $quote = PricingQuote::query()->with('discounts')->latest('id')->firstOrFail();
    expect($quote->discounts->first()->code_snapshot)->toBe('VIP700')
        ->and($quote->discounts->first()->pricing_promotion_id)->toBeNull();
});
