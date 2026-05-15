<?php

namespace App\Import\Rules;

use App\Import\Contracts\ImportRuleInterface;
use App\Import\Data\ProductData;

// Skip anything over $1000 — supplier occasionally sends premium items we don't carry.
// Exactly $1000 is fine.
class HighPriceRule implements ImportRuleInterface
{
    private const PRICE_LIMIT = 1000.0;

    public function isSatisfiedBy(ProductData $data): bool
    {
        return $data->price <= self::PRICE_LIMIT;
    }

    public function getReason(): string
    {
        return sprintf('Price exceeds $%.2f', self::PRICE_LIMIT);
    }
}
