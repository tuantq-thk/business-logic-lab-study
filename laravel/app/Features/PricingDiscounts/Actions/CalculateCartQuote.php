<?php

namespace App\Features\PricingDiscounts\Actions;

use App\Features\PricingDiscounts\Enums\DiscountKind;
use App\Features\PricingDiscounts\ValueObjects\CartLine;
use App\Features\PricingDiscounts\ValueObjects\DiscountRule;
use App\Features\PricingDiscounts\ValueObjects\PriceQuote;
use InvalidArgumentException;

final class CalculateCartQuote
{
    /**
     * @param  list<CartLine>  $lines
     * @param  list<DiscountRule>  $rules
     */
    public function handle(array $lines, array $rules): PriceQuote
    {
        if ($lines === []) {
            throw new InvalidArgumentException('A quote requires at least one cart line.');
        }

        $currency = $lines[0]->unitPrice->currency;

        foreach ($lines as $line) {
            if (! $line instanceof CartLine) {
                throw new InvalidArgumentException('Quote lines must be CartLine instances.');
            }

            if ($line->unitPrice->currency !== $currency) {
                throw new InvalidArgumentException('All cart lines must use the same currency.');
            }
        }

        $subtotal = array_sum(array_map(
            static fn (CartLine $line): int => $line->subtotalMinor(),
            $lines,
        ));
        $eligibleRules = array_values(array_filter(
            $rules,
            static fn (DiscountRule $rule): bool => $rule->active
                && $rule->minimumSubtotalMinor <= $subtotal,
        ));

        $stackedRules = array_values(array_filter(
            $eligibleRules,
            static fn (DiscountRule $rule): bool => $rule->stackable,
        ));
        usort($stackedRules, static fn (DiscountRule $left, DiscountRule $right): int => $left->priority <=> $right->priority);

        $stackedDiscount = 0;
        $stackedBase = $subtotal;
        $stackedAppliedDiscounts = [];
        foreach ($stackedRules as $rule) {
            $discount = min($stackedBase, $this->discountFor($stackedBase, $rule));
            $stackedDiscount += $discount;
            $stackedBase -= $discount;
            $stackedAppliedDiscounts[] = [
                'code' => $rule->code,
                'amountMinor' => $discount,
            ];
        }

        $exclusiveRules = array_values(array_filter(
            $eligibleRules,
            static fn (DiscountRule $rule): bool => ! $rule->stackable,
        ));
        $bestExclusiveRule = null;
        $bestExclusiveDiscount = 0;
        foreach ($exclusiveRules as $rule) {
            $discount = min($subtotal, $this->discountFor($subtotal, $rule));

            if ($discount > $bestExclusiveDiscount) {
                $bestExclusiveRule = $rule;
                $bestExclusiveDiscount = $discount;
            }
        }

        if ($bestExclusiveDiscount > $stackedDiscount) {
            return new PriceQuote(
                lines: $lines,
                currency: $currency,
                subtotalMinor: $subtotal,
                discountMinor: $bestExclusiveDiscount,
                totalMinor: $subtotal - $bestExclusiveDiscount,
                appliedRuleCodes: $bestExclusiveRule === null ? [] : [$bestExclusiveRule->code],
                appliedDiscounts: $bestExclusiveRule === null ? [] : [[
                    'code' => $bestExclusiveRule->code,
                    'amountMinor' => $bestExclusiveDiscount,
                ]],
            );
        }

        return new PriceQuote(
            lines: $lines,
            currency: $currency,
            subtotalMinor: $subtotal,
            discountMinor: $stackedDiscount,
            totalMinor: $subtotal - $stackedDiscount,
            appliedRuleCodes: array_column($stackedAppliedDiscounts, 'code'),
            appliedDiscounts: $stackedAppliedDiscounts,
        );
    }

    private function discountFor(int $baseMinor, DiscountRule $rule): int
    {
        return match ($rule->kind) {
            DiscountKind::Fixed => $rule->value,
            DiscountKind::Percentage => intdiv(($baseMinor * $rule->value) + 50, 100),
        };
    }
}
