<?php

namespace App\Import;

use App\Import\Contracts\ProductRepositoryInterface;
use App\Import\Data\ProductData;
use App\Models\Product;

class ProductRepository implements ProductRepositoryInterface
{
    public function save(ProductData $data): void
    {
        $product = Product::firstOrNew(['strProductCode' => $data->productCode]);
        $isNew = !$product->exists;

        $product->fill([
            'strProductName' => $data->productName,
            'strProductDesc' => $data->productDesc,
            'strProductCode' => $data->productCode,
            'intStock' => $data->stock,
            'decPrice' => $data->price,
            // Keep the original discontinued date on re-imports — don't overwrite it with today.
            'dtmDiscontinued' => $data->discontinued
                ? ($product->dtmDiscontinued ?? now())
                : null,
        ]);

        if ($isNew) {
            $product->dtmAdded = now(); // only set on first insert, never overwritten
        }

        $product->save();
    }
}
