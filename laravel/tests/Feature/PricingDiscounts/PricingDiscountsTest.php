<?php

use App\Features\PricingDiscounts\Models\PricingProduct;
use App\Features\PricingDiscounts\Models\PricingQuote;
use App\Models\CatalogSetting;
use App\Models\User;
use Database\Seeders\AdminCatalogSeeder;
use Database\Seeders\PricingDiscountsSeeder;
use Illuminate\Support\Facades\App;

beforeEach(function () {
    $this->seed(AdminCatalogSeeder::class);
    $this->seed(PricingDiscountsSeeder::class);
    CatalogSetting::query()->where('key', CatalogSetting::CATALOG_KEY)->update(['enabled' => true]);
});

test('an enabled pricing lab renders its deterministic quote demo', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('labs.pricing-discounts.index'))
        ->assertOk()
        ->assertSee('Mỗi quote đều phải giải thích được')
        ->assertSee('WELCOME10')
        ->assertSee('SAVE300')
        ->assertSee('BULK15')
        ->assertSee('$42.75');
});

test('a disabled pricing lab is hidden and direct access is blocked', function () {
    CatalogSetting::query()->where('key', 'pricing-discounts')->update(['enabled' => false]);

    $this->actingAs(User::factory()->create())
        ->get('/home')
        ->assertOk()
        ->assertDontSee('pricing-discounts');

    $this->actingAs(User::factory()->create())
        ->get(route('labs.pricing-discounts.index'))
        ->assertNotFound();
});

test('stale session selections fall back to the current catalog', function () {
    $this->actingAs(User::factory()->create())
        ->withSession([
            'pricing_quote_inputs' => [
                'quantities' => ['book-php' => 1, 'book-sql' => 1],
                'rules' => ['DELETED_PROMOTION'],
            ],
            'pricing_quote_id' => null,
        ])
        ->get(route('labs.pricing-discounts.index'))
        ->assertOk()
        ->assertSee('WELCOME10')
        ->assertDontSee('DELETED_PROMOTION');
});

test('the pricing demo renders its supported locales', function () {
    $learner = User::factory()->create();
    $defaultLocale = App::getLocale();

    try {
        App::setLocale('vi');
        $this->actingAs($learner)
            ->get(route('labs.pricing-discounts.index'))
            ->assertSee('Mỗi quote đều phải giải thích được');

        App::setLocale('en');
        $this->actingAs($learner)
            ->get(route('labs.pricing-discounts.index'))
            ->assertSee('Make every quote explainable')
            ->assertSee('Amounts are calculated as integer USD cents; no binary floating point is used.');
    } finally {
        App::setLocale($defaultLocale);
    }
});

test('a learner can recalculate a quote with selected quantities and rules', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('labs.pricing-discounts.quote'), [
            'quantities' => ['book-php' => 1, 'book-sql' => 2],
            'rules' => ['VIP700'],
        ])
        ->assertRedirect(route('labs.pricing-discounts.index'));

    $this->assertDatabaseHas('pricing_quotes', [
        'currency' => 'USD',
        'subtotal_minor' => 5500,
        'discount_minor' => 700,
        'total_minor' => 4800,
    ]);
    $this->assertDatabaseHas('pricing_quote_lines', [
        'sku_snapshot' => 'book-sql',
        'quantity' => 2,
        'unit_price_minor' => 2000,
        'line_total_minor' => 4000,
    ]);
    $this->assertDatabaseHas('pricing_quote_discounts', [
        'code_snapshot' => 'VIP700',
        'discount_minor' => 700,
    ]);

    $this->get(route('labs.pricing-discounts.index'))
        ->assertOk()
        ->assertSee('$55.00')
        ->assertSee('$7.00')
        ->assertSee('$48.00')
        ->assertSee('VIP700');
});

test('each quote submission creates an independent persisted quote', function () {
    $learner = User::factory()->create();

    foreach ([1, 2, 3] as $quantity) {
        $this->actingAs($learner)
            ->post(route('labs.pricing-discounts.quote'), [
                'quantities' => ['book-php' => $quantity, 'book-sql' => 1],
                'rules' => [],
            ])
            ->assertRedirect(route('labs.pricing-discounts.index'));
    }

    expect(PricingQuote::query()->where('created_by', $learner->id)->count())->toBe(3);
});

test('invalid quote input is returned to the quote block with translated errors', function () {
    $this->followingRedirects()
        ->actingAs(User::factory()->create())
        ->from(route('labs.pricing-discounts.index'))
        ->post(route('labs.pricing-discounts.quote'), [
            'quantities' => ['book-php' => -1, 'book-sql' => 1],
            'rules' => ['UNKNOWN'],
        ])
        ->assertOk()
        ->assertSee('số lượng Laravel trong thực tế')
        ->assertSee('promotion');
});

test('malformed promotion input returns validation errors instead of a server error', function () {
    $this->actingAs(User::factory()->create())
        ->from(route('labs.pricing-discounts.index'))
        ->post(route('labs.pricing-discounts.quote'), [
            'quantities' => ['book-php' => 1, 'book-sql' => 1],
            'rules' => 'VIP700',
        ])
        ->assertRedirect(route('labs.pricing-discounts.index'))
        ->assertSessionHasErrors('rules');
});

test('a quote without promotions is supported', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('labs.pricing-discounts.quote'), [
            'quantities' => ['book-php' => 1, 'book-sql' => 1],
            'rules' => null,
        ])
        ->assertRedirect(route('labs.pricing-discounts.index'));

    $this->get(route('labs.pricing-discounts.index'))
        ->assertOk()
        ->assertSee('$35.00')
        ->assertSee('$0.00');
});

test('a persisted quote keeps its price snapshot when the product price changes', function () {
    $learner = User::factory()->create();

    $this->actingAs($learner)
        ->post(route('labs.pricing-discounts.quote'), [
            'quantities' => ['book-php' => 1, 'book-sql' => 1],
            'rules' => [],
        ])
        ->assertRedirect(route('labs.pricing-discounts.index'));

    PricingProduct::query()
        ->where('sku', 'book-php')
        ->update(['unit_price_minor' => 2500]);

    $this->actingAs($learner)
        ->get(route('labs.pricing-discounts.index'))
        ->assertOk()
        ->assertSee('$35.00')
        ->assertSee('$15.00');
});

test('a persisted quote keeps its line snapshot when the product is deleted', function () {
    $learner = User::factory()->create();

    $this->actingAs($learner)
        ->post(route('labs.pricing-discounts.quote'), [
            'quantities' => ['book-php' => 1, 'book-sql' => 1],
            'rules' => [],
        ])
        ->assertRedirect(route('labs.pricing-discounts.index'));

    PricingProduct::query()->where('sku', 'book-php')->firstOrFail()->delete();

    $this->assertDatabaseHas('pricing_quote_lines', [
        'sku_snapshot' => 'book-php',
        'name_snapshot' => __('pricing_discounts.demo.products.php'),
        'pricing_product_id' => null,
        'unit_price_minor' => 1500,
    ]);
});

test('an owner can open a persisted quote snapshot but another user cannot', function () {
    $owner = User::factory()->create();

    $this->actingAs($owner)
        ->post(route('labs.pricing-discounts.quote'), [
            'quantities' => ['book-php' => 1, 'book-sql' => 1],
            'rules' => ['SAVE300'],
        ])
        ->assertRedirect();

    $quoteId = PricingQuote::query()->latest('id')->value('id');

    $this->actingAs($owner)
        ->get(route('labs.pricing-discounts.quotes.show', $quoteId))
        ->assertOk()
        ->assertSee('SAVE300')
        ->assertSee('$32.00');

    $this->actingAs(User::factory()->create())
        ->get(route('labs.pricing-discounts.quotes.show', $quoteId))
        ->assertNotFound();
});
