<?php

use App\Features\PricingDiscounts\Actions\CalculateCartQuote;
use App\Features\PricingDiscounts\Enums\DiscountKind;
use App\Features\PricingDiscounts\ValueObjects\CartLine;
use App\Features\PricingDiscounts\ValueObjects\DiscountRule;
use App\Features\PricingDiscounts\ValueObjects\Money;

function pricingLines(): array
{
    return [
        new CartLine('book-php', 'Laravel in Practice', 2, new Money(1500, 'USD')),
        new CartLine('book-sql', 'SQL That Works', 1, new Money(2000, 'USD')),
    ];
}

test('calculates subtotal and total with no discount', function () {
    $quote = (new CalculateCartQuote)->handle(pricingLines(), []);

    expect($quote->subtotalMinor)->toBe(5000)
        ->and($quote->discountMinor)->toBe(0)
        ->and($quote->totalMinor)->toBe(5000)
        ->and($quote->currency)->toBe('USD');
});

test('applies a percentage discount with half-up minor-unit rounding', function () {
    $quote = (new CalculateCartQuote)->handle(
        [new CartLine('book', 'A book', 1, new Money(1001, 'USD'))],
        [new DiscountRule('WELCOME10', DiscountKind::Percentage, 10)],
    );

    expect($quote->discountMinor)->toBe(100)
        ->and($quote->totalMinor)->toBe(901)
        ->and($quote->appliedRuleCodes)->toBe(['WELCOME10']);
});

test('ignores an ineligible rule below its minimum subtotal', function () {
    $quote = (new CalculateCartQuote)->handle(
        pricingLines(),
        [new DiscountRule('BIG-ORDER', DiscountKind::Fixed, 1000, minimumSubtotalMinor: 6000)],
    );

    expect($quote->discountMinor)->toBe(0)
        ->and($quote->appliedRuleCodes)->toBe([]);
});

test('chooses the best result between stacked and exclusive rules', function () {
    $quote = (new CalculateCartQuote)->handle(
        pricingLines(),
        [
            new DiscountRule('STACK-10', DiscountKind::Percentage, 10, priority: 10, stackable: true),
            new DiscountRule('STACK-5', DiscountKind::Percentage, 5, priority: 20, stackable: true),
            new DiscountRule('VIP-700', DiscountKind::Fixed, 700, priority: 5, stackable: false),
        ],
    );

    expect($quote->discountMinor)->toBe(725)
        ->and($quote->totalMinor)->toBe(4275)
        ->and($quote->appliedRuleCodes)->toBe(['STACK-10', 'STACK-5']);
});

test('never discounts below zero and rejects mixed currencies', function () {
    $quote = (new CalculateCartQuote)->handle(
        pricingLines(),
        [new DiscountRule('FREE', DiscountKind::Fixed, 999999)],
    );

    expect($quote->discountMinor)->toBe(5000)
        ->and($quote->totalMinor)->toBe(0);

    expect(fn () => (new CalculateCartQuote)->handle([
        new CartLine('book', 'A book', 1, new Money(1000, 'EUR')),
        new CartLine('book-2', 'Another book', 1, new Money(1000, 'USD')),
    ], []))->toThrow(InvalidArgumentException::class);
});

test('formats supported currencies from minor units', function () {
    expect(Money::formatMinor(4275, 'USD'))->toBe('$42.75')
        ->and(Money::formatMinor(4275, 'EUR'))->toBe('€42.75')
        ->and(Money::formatMinor(4275, 'JPY'))->toBe('¥4,275');
});
