<?php

namespace Tests\Unit\Import\Rules;

use App\Import\Data\ProductData;
use App\Import\Rules\LowValueLowStockRule;
use PHPUnit\Framework\TestCase;

class LowValueLowStockRuleTest extends TestCase
{
    private LowValueLowStockRule $rule;

    protected function setUp(): void
    {
        $this->rule = new LowValueLowStockRule();
    }

    /** Creates a minimal ProductData for rule testing. */
    private function makeProduct(float $price, ?int $stock): ProductData
    {
        return new ProductData('P0001', 'Test', 'Desc', $stock, $price, false);
    }

    public function test_skips_when_price_below_5_and_stock_below_10(): void
    {
        $this->assertFalse($this->rule->isSatisfiedBy($this->makeProduct(4.99, 9)));
    }

    public function test_passes_when_price_exactly_5_and_stock_exactly_10(): void
    {
        // Boundary: both thresholds at the limit — should pass
        $this->assertTrue($this->rule->isSatisfiedBy($this->makeProduct(5.0, 10)));
    }

    public function test_passes_when_price_is_5_but_stock_below_10(): void
    {
        // Price meets threshold even though stock is low
        $this->assertTrue($this->rule->isSatisfiedBy($this->makeProduct(5.0, 3)));
    }

    public function test_passes_when_price_below_5_but_stock_is_10_or_more(): void
    {
        // P0019 case: price=3.44, stock=23 — should be imported
        $this->assertTrue($this->rule->isSatisfiedBy($this->makeProduct(3.44, 23)));
    }

    public function test_passes_when_price_5_or_more_but_stock_below_10(): void
    {
        // P0004 case: price=24.55, stock=1 — should be imported
        $this->assertTrue($this->rule->isSatisfiedBy($this->makeProduct(24.55, 1)));
    }

    public function test_skips_when_price_is_zero_and_stock_is_zero(): void
    {
        $this->assertFalse($this->rule->isSatisfiedBy($this->makeProduct(0.0, 0)));
    }

    public function test_treats_null_stock_as_zero_and_skips_with_low_price(): void
    {
        // A product with an unknown stock level and price below threshold is conservatively skipped
        $this->assertFalse($this->rule->isSatisfiedBy($this->makeProduct(3.0, null)));
    }

    public function test_treats_null_stock_as_zero_but_passes_when_price_above_threshold(): void
    {
        // Unknown stock + high price → import anyway (price condition not met)
        $this->assertTrue($this->rule->isSatisfiedBy($this->makeProduct(10.0, null)));
    }

    public function test_get_reason_returns_non_empty_string(): void
    {
        $this->assertNotEmpty($this->rule->getReason());
    }
}
