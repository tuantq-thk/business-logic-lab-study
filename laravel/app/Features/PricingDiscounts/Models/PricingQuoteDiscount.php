<?php

namespace App\Features\PricingDiscounts\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'pricing_quote_id',
    'pricing_promotion_id',
    'code_snapshot',
    'discount_minor',
])]
class PricingQuoteDiscount extends Model
{
    public function quote(): BelongsTo
    {
        return $this->belongsTo(PricingQuote::class, 'pricing_quote_id');
    }

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(PricingPromotion::class, 'pricing_promotion_id');
    }

    protected function casts(): array
    {
        return [
            'discount_minor' => 'integer',
        ];
    }
}
