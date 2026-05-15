<?php

namespace Tests\Unit\Import;

use App\Import\ProductParser;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ProductParserTest extends TestCase
{
    private ProductParser $parser;

    protected function setUp(): void
    {
        $this->parser = new ProductParser();
    }

    /** Builds a valid 6-element row, allowing per-index overrides. */
    private function validRow(array $overrides = []): array
    {
        $row = ['P0001', 'TV', '32" TV', '10', '399.99', ''];
        foreach ($overrides as $index => $value) {
            $row[$index] = $value;
        }
        return $row;
    }

    // -------------------------------------------------------------------------
    // Happy path
    // -------------------------------------------------------------------------

    public function test_parses_valid_row_correctly(): void
    {
        $data = $this->parser->parse($this->validRow());

        $this->assertSame('P0001', $data->productCode);
        $this->assertSame('TV', $data->productName);
        $this->assertSame('32" TV', $data->productDesc);
        $this->assertSame(10, $data->stock);
        $this->assertEqualsWithDelta(399.99, $data->price, 0.001);
        $this->assertFalse($data->discontinued);
    }

    public function test_sets_discontinued_true_for_yes(): void
    {
        $data = $this->parser->parse($this->validRow([5 => 'yes']));
        $this->assertTrue($data->discontinued);
    }

    public function test_sets_discontinued_true_for_yes_uppercase(): void
    {
        $data = $this->parser->parse($this->validRow([5 => 'YES']));
        $this->assertTrue($data->discontinued);
    }

    public function test_sets_discontinued_false_for_empty_field(): void
    {
        $data = $this->parser->parse($this->validRow([5 => '']));
        $this->assertFalse($data->discontinued);
    }

    // -------------------------------------------------------------------------
    // P0015 — dollar sign in price
    // -------------------------------------------------------------------------

    public function test_strips_dollar_sign_from_price(): void
    {
        $row = ['P0015', 'Bluray Player', 'Excellent picture', '32', '$4.33', ''];
        $data = $this->parser->parse($row);
        $this->assertEqualsWithDelta(4.33, $data->price, 0.001);
    }

    // -------------------------------------------------------------------------
    // P0007 — missing stock
    // -------------------------------------------------------------------------

    public function test_returns_null_stock_for_empty_stock_field(): void
    {
        $row = ['P0007', '24" Monitor', 'Awesome', '', '35.99', ''];
        $data = $this->parser->parse($row);
        $this->assertNull($data->stock);
    }

    // -------------------------------------------------------------------------
    // P0011 — too few columns
    // -------------------------------------------------------------------------

    public function test_throws_for_row_with_too_few_columns(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/got 3/');

        $this->parser->parse(['P0011', 'Misc Cables', 'error in export']);
    }

    // -------------------------------------------------------------------------
    // P0017 — unquoted comma in description (7 fields)
    // -------------------------------------------------------------------------

    public function test_merges_extra_description_columns_from_unquoted_comma(): void
    {
        // As league/csv parses it — the comma inside the description creates an extra field
        $row = ['P0017', 'CPU', 'Processing power', ' ideal for multimedia', '4', '4.22', ''];
        $data = $this->parser->parse($row);

        $this->assertSame('P0017', $data->productCode);
        // Comma is restored without added spaces — original text is preserved as-is
        $this->assertSame('Processing power, ideal for multimedia', $data->productDesc);
        $this->assertSame(4, $data->stock);
        $this->assertEqualsWithDelta(4.22, $data->price, 0.001);
    }

    // -------------------------------------------------------------------------
    // Invalid data
    // -------------------------------------------------------------------------

    public function test_throws_for_non_numeric_price(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/price/i');

        $this->parser->parse(['P0001', 'Test', 'Desc', '10', 'N/A', '']);
    }

    public function test_throws_for_negative_stock(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/stock/i');

        $this->parser->parse(['P0001', 'Test', 'Desc', '-5', '10.00', '']);
    }

    public function test_throws_for_float_stock(): void
    {
        // '10.5' passes is_numeric but is not a valid integer stock quantity
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/stock/i');

        $this->parser->parse(['P0001', 'Test', 'Desc', '10.5', '10.00', '']);
    }

    public function test_trims_whitespace_from_all_string_fields(): void
    {
        $row = ['  P0001  ', '  TV  ', '  32" TV  ', '10', '399.99', ''];
        $data = $this->parser->parse($row);

        $this->assertSame('P0001', $data->productCode);
        $this->assertSame('TV', $data->productName);
        $this->assertSame('32" TV', $data->productDesc);
    }
}
