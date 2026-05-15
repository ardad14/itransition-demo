<?php

namespace App\Import\Contracts;

use App\Import\Data\ProductData;

// Each import rule answers one question: should this product be imported?
// true  = yes, import it
// false = no, skip it
interface ImportRuleInterface
{
    public function isSatisfiedBy(ProductData $data): bool;

    // What to show in the report when this rule rejects something
    public function getReason(): string;
}
