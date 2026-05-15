<?php

namespace App\Import\Rules;

use App\Import\Contracts\ImportRuleInterface;
use App\Import\Data\ProductData;

// Skip products that are cheap AND low on stock — not worth importing.
// Both conditions must hold; either one alone is fine.
class LowValueLowStockRule implements ImportRuleInterface
{
    private const PRICE_THRESHOLD = 5.0;
    private const STOCK_THRESHOLD = 10;

    public function isSatisfiedBy(ProductData $data): bool
    {
        // Unknown stock is treated as 0 — better to skip than assume
        $stock = $data->stock ?? 0;

        if ($data->price < self::PRICE_THRESHOLD && $stock < self::STOCK_THRESHOLD) {
            return false;
        }

        return true;
    }

    public function getReason(): string
    {
        return sprintf(
            'Price is below $%.2f and stock is below %d',
            self::PRICE_THRESHOLD,
            self::STOCK_THRESHOLD
        );
    }
}
