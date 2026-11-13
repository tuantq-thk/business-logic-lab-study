<?php

namespace App\Features\PricingDiscounts\ValueObjects;

use InvalidArgumentException;

final readonly class Money
{
    public function __construct(
        public int $amountMinor,
        public string $currency,
    ) {
        if ($amountMinor < 0) {
            throw new InvalidArgumentException('Money amount cannot be negative.');
        }

        if (preg_match('/\A[A-Z]{3}\z/', $currency) !== 1) {
            throw new InvalidArgumentException('Currency must be a three-letter uppercase ISO code.');
        }
    }

    public static function formatMinor(int $amountMinor, string $currency): string
    {
        $currency = strtoupper($currency);
        new self($amountMinor, $currency);
        $metadata = match ($currency) {
            'USD' => ['symbol' => '$', 'exponent' => 2],
            'EUR' => ['symbol' => '€', 'exponent' => 2],
            'GBP' => ['symbol' => '£', 'exponent' => 2],
            'JPY' => ['symbol' => '¥', 'exponent' => 0],
            default => ['symbol' => $currency.' ', 'exponent' => 2],
        };
        $divisor = 10 ** $metadata['exponent'];
        $major = intdiv($amountMinor, $divisor);
        $minor = $amountMinor % $divisor;
        $fraction = $metadata['exponent'] === 0
            ? ''
            : '.'.str_pad((string) $minor, $metadata['exponent'], '0', STR_PAD_LEFT);

        return $metadata['symbol'].number_format($major).$fraction;
    }
}
