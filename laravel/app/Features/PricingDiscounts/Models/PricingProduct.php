<?php

namespace App\Features\PricingDiscounts\Models;

use App\Features\PricingDiscounts\ValueObjects\CartLine;
use App\Features\PricingDiscounts\ValueObjects\Money;
use Database\Factories\PricingDiscounts\PricingProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'sku',
    'name_key',
    'unit_price_minor',
    'currency',
    'active',
])]
class PricingProduct extends Model
{
    /** @use HasFactory<PricingProductFactory> */
    use HasFactory;

    protected static function newFactory(): PricingProductFactory
    {
        return PricingProductFactory::new();
    }

    public function quoteLines(): HasMany
    {
        return $this->hasMany(PricingQuoteLine::class);
    }

    /**
     * @param  Builder<PricingProduct>  $query
     * @return Builder<PricingProduct>
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function toCartLine(int $quantity): CartLine
    {
        return new CartLine(
            sku: $this->sku,
            name: __($this->name_key),
            quantity: $quantity,
            unitPrice: new Money($this->unit_price_minor, $this->currency),
        );
    }

    protected function casts(): array
    {
        return [
            'unit_price_minor' => 'integer',
            'active' => 'boolean',
        ];
    }
}
