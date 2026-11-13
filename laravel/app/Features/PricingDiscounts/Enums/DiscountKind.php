<?php

namespace App\Features\PricingDiscounts\Enums;

enum DiscountKind: string
{
    case Fixed = 'fixed';
    case Percentage = 'percentage';
}
