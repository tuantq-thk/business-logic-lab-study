<?php

namespace App\Features\PricingDiscounts\Models;

use App\Features\PricingDiscounts\ValueObjects\CartLine;
use App\Features\PricingDiscounts\ValueObjects\PriceQuote;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'created_by',
    'currency',
    'subtotal_minor',
    'discount_minor',
    'total_minor',
])]
class PricingQuote extends Model
{
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PricingQuoteLine::class);
    }

    public function discounts(): HasMany
    {
        return $this->hasMany(PricingQuoteDiscount::class);
    }

    public function toPriceQuote(): PriceQuote
    {
        return new PriceQuote(
            lines: $this->lines->map(fn (PricingQuoteLine $line): CartLine => $line->toCartLine($this->currency))->values()->all(),
            currency: $this->currency,
            subtotalMinor: $this->subtotal_minor,
            discountMinor: $this->discount_minor,
            totalMinor: $this->total_minor,
            appliedRuleCodes: $this->discounts->pluck('code_snapshot')->values()->all(),
            appliedDiscounts: $this->discounts->map(fn (PricingQuoteDiscount $discount): array => [
                'code' => $discount->code_snapshot,
                'amountMinor' => $discount->discount_minor,
            ])->values()->all(),
        );
    }

    protected function casts(): array
    {
        return [
            'subtotal_minor' => 'integer',
            'discount_minor' => 'integer',
            'total_minor' => 'integer',
        ];
    }
}
