<?php

namespace App\Contracts;

use App\DTOs\ProductData;

interface SalesPlatformInterface
{
    public function createProduct(ProductData $product): array;
}
