<?php

namespace App\Import\Contracts;

use App\Import\Data\ProductData;

interface ProductRepositoryInterface
{
    /**
     * Insert or update a product by its product code.
     *
     * @throws \Throwable on any DB error
     */
    public function save(ProductData $data): void;
}
