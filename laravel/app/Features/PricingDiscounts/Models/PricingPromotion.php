<?php

namespace App\Features\PricingDiscounts\Models;

use App\Features\PricingDiscounts\Enums\DiscountKind;
use App\Features\PricingDiscounts\ValueObjects\DiscountRule;
use Database\Factories\PricingDiscounts\PricingPromotionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

#[Fillable([
    'code',
    'kind',
    'value',
    'minimum_subtotal_minor',
    'priority',
    'stackable',
    'active',
    'starts_at',
    'ends_at',
])]
class PricingPromotion extends Model
{
    /** @use HasFactory<PricingPromotionFactory> */
    use HasFactory;

    protected static function newFactory(): PricingPromotionFactory
    {
        return PricingPromotionFactory::new();
    }

    public function quoteDiscounts(): HasMany
    {
        return $this->hasMany(PricingQuoteDiscount::class);
    }

    /**
     * @param  Builder<PricingPromotion>  $query
     * @return Builder<PricingPromotion>
     */
    public function scopeCurrentlyActive(Builder $query, Carbon $at): Builder
    {
        return $query
            ->where('active', true)
            ->where(function (Builder $query) use ($at): void {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', $at);
            })
            ->where(function (Builder $query) use ($at): void {
                $query->whereNull('ends_at')->orWhere('ends_at', '>', $at);
            });
    }

    public function toDiscountRule(): DiscountRule
    {
        return new DiscountRule(
            code: $this->code,
            kind: DiscountKind::from($this->kind),
            value: $this->value,
            minimumSubtotalMinor: $this->minimum_subtotal_minor,
            priority: $this->priority,
            stackable: $this->stackable,
            active: $this->active,
        );
    }

    protected function casts(): array
    {
        return [
            'value' => 'integer',
            'minimum_subtotal_minor' => 'integer',
            'priority' => 'integer',
            'stackable' => 'boolean',
            'active' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }
}
