<?php

namespace Tests\Unit\Import\Rules;

use App\Import\Data\ProductData;
use App\Import\Rules\HighPriceRule;
use PHPUnit\Framework\TestCase;

class HighPriceRuleTest extends TestCase
{
    private HighPriceRule $rule;

    protected function setUp(): void
    {
        $this->rule = new HighPriceRule();
    }

    private function makeProduct(float $price): ProductData
    {
        return new ProductData('P0001', 'Test', 'Desc', 10, $price, false);
    }

    public function test_passes_when_price_is_below_1000(): void
    {
        $this->assertTrue($this->rule->isSatisfiedBy($this->makeProduct(999.99)));
    }

    public function test_passes_when_price_is_exactly_1000(): void
    {
        // Boundary: exactly 1000 is allowed
        $this->assertTrue($this->rule->isSatisfiedBy($this->makeProduct(1000.0)));
    }

    public function test_skips_when_price_is_above_1000(): void
    {
        // P0027 case: price=1200.03
        $this->assertFalse($this->rule->isSatisfiedBy($this->makeProduct(1200.03)));
    }

    public function test_skips_when_price_is_1000_01(): void
    {
        $this->assertFalse($this->rule->isSatisfiedBy($this->makeProduct(1000.01)));
    }

    public function test_get_reason_returns_non_empty_string(): void
    {
        $this->assertNotEmpty($this->rule->getReason());
    }
}
