<?php

namespace App\Contracts;

use App\DTOs\ProductData;
use App\DTOs\VariationData;

interface ProductMapperInterface
{
    public function mapProduct(array $product): ProductData;

    public function mapVariation(array $variation): VariationData;

    public function getProductCodeFromVariation(array $variation): int;
}
