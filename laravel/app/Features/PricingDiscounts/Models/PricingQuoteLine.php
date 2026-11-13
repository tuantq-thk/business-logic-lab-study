<?php

namespace App\Features\PricingDiscounts\Models;

use App\Features\PricingDiscounts\ValueObjects\CartLine;
use App\Features\PricingDiscounts\ValueObjects\Money;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'pricing_quote_id',
    'pricing_product_id',
    'sku_snapshot',
    'name_snapshot',
    'quantity',
    'unit_price_minor',
    'line_total_minor',
])]
class PricingQuoteLine extends Model
{
    public function quote(): BelongsTo
    {
        return $this->belongsTo(PricingQuote::class, 'pricing_quote_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(PricingProduct::class, 'pricing_product_id');
    }

    public function toCartLine(string $currency): CartLine
    {
        return new CartLine(
            sku: $this->sku_snapshot,
            name: $this->name_snapshot,
            quantity: $this->quantity,
            unitPrice: new Money($this->unit_price_minor, $currency),
        );
    }

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price_minor' => 'integer',
            'line_total_minor' => 'integer',
        ];
    }
}
