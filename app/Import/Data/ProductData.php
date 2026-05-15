<?php

namespace App\Import\Data;

// One product row from the supplier CSV, already parsed and validated.
readonly class ProductData
{
    public function __construct(
        public string  $productCode,
        public string  $productName,
        public string  $productDesc,
        public ?int    $stock,       // null if the supplier left the field blank (e.g. P0007)
        public float   $price,
        public bool    $discontinued,
    ) {}
}
