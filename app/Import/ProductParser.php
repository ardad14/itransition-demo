<?php

namespace App\Import;

use App\Import\Data\ProductData;
use InvalidArgumentException;

// Turns a raw CSV row into a typed ProductData object.
//
// The supplier file has a few recurring data issues we deal with here:
//   - P0011: truncated row ("error in export") — thrown as an exception
//   - P0015: price written as "$4.33" with a dollar sign — stripped
//   - P0017: unquoted comma inside the description — extra fields merged back
class ProductParser
{
    private const EXPECTED_COLUMNS = 6;

    // Column positions in a normal row
    private const COL_CODE = 0;
    private const COL_NAME = 1;
    private const COL_DESC = 2;
    private const COL_STOCK = 3;
    private const COL_PRICE = 4;
    private const COL_DISCONTINUED = 5;

    /** @throws InvalidArgumentException if the row is too broken to parse */
    public function parse(array $row): ProductData
    {
        $row = $this->normalizeColumnCount($row);

        return new ProductData(
            productCode: trim($row[self::COL_CODE]),
            productName: trim($row[self::COL_NAME]),
            productDesc: trim($row[self::COL_DESC]),
            stock: $this->parseStock($row[self::COL_STOCK], $row[self::COL_CODE]),
            price: $this->parsePrice($row[self::COL_PRICE], $row[self::COL_CODE]),
            discontinued: $this->parseDiscontinued($row[self::COL_DISCONTINUED]),
        );
    }

    // Makes sure we always have exactly 6 fields to work with.
    //
    // Fewer than 6 → the row is garbage, throw.
    // More than 6  → the description had an unquoted comma, so the CSV parser
    //                split it into extra columns. Glue those middle pieces back.
    //
    // Example (P0017 comes in as 7 fields):
    //   ['P0017','CPU','Processing power',' ideal for multimedia','4','4.22','']
    //   → description = 'Processing power, ideal for multimedia'
    //   → ['P0017','CPU','Processing power, ideal for multimedia','4','4.22','']
    private function normalizeColumnCount(array $row): array
    {
        $count = count($row);

        if ($count < self::EXPECTED_COLUMNS) {
            throw new InvalidArgumentException(
                "Invalid row format: expected " . self::EXPECTED_COLUMNS . " columns, got {$count}"
            );
        }

        if ($count === self::EXPECTED_COLUMNS) {
            return $row;
        }

        // Last 3 fields are always stock / price / discontinued.
        // Everything between name and those 3 is the description split by commas.
        $extraCount = $count - self::EXPECTED_COLUMNS;
        $descParts = array_slice($row, self::COL_DESC, $extraCount + 1);
        $description = implode(',', $descParts);

        return [
            $row[self::COL_CODE],
            $row[self::COL_NAME],
            $description,
            $row[$count - 3], // stock
            $row[$count - 2], // price
            $row[$count - 1], // discontinued
        ];
    }

    // Some rows have prices like "$4.33" — strip the dollar sign before parsing.
    private function parsePrice(string $raw, string $productCode): float
    {
        $cleaned = trim(str_replace('$', '', $raw));

        if (!is_numeric($cleaned)) {
            throw new InvalidArgumentException(
                "Invalid price value '{$raw}' for product {$productCode}"
            );
        }

        return (float)$cleaned;
    }

    // Returns null if the field is blank (supplier sometimes omits stock, e.g. P0007).
    // ctype_digit is intentional — rejects floats like '10.5' and negatives like '-5'.
    private function parseStock(string $raw, string $productCode): ?int
    {
        $trimmed = trim($raw);

        if ($trimmed === '') {
            return null;
        }

        if (!ctype_digit($trimmed)) {
            throw new InvalidArgumentException(
                "Invalid stock value '{$raw}' for product {$productCode}"
            );
        }

        return (int)$trimmed;
    }

    private function parseDiscontinued(string $raw): bool
    {
        return strtolower(trim($raw)) === 'yes';
    }
}
