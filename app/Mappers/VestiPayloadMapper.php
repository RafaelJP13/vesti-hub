<?php

namespace App\Mappers;

use App\DTOs\ProductData;
use App\DTOs\VariationData;

class VestiPayloadMapper
{
    /**
     * @return array{products: array<int, array<string, mixed>>}
     */
    public function map(ProductData $product): array
    {
        $productPayload = [
            'integration_id' => (string) $product->code,
            'code' => (string) $product->code,
            'name' => $product->name,
            'active' => true,
            'description' => $product->description,
            'price' => $product->price,
            'promotion' => $product->promotionalPrice !== null,
            'price_promotional' => $product->promotionalPrice,
            'variations' => array_map(
                fn (VariationData $variation): array => [
                    'sku' => $variation->sku,
                    'size' => $variation->size,
                    'color' => $variation->color,
                    'quantity' => $variation->quantity,
                    'order' => $variation->ordering,
                    'unit_type' => $variation->unitMeasurement,
                ],
                $product->variations,
            ),
        ];

        if ($product->composition !== null) {
            $productPayload['composition'] = $product->composition;
        }

        if ($product->brand !== null) {
            $productPayload['brand'] = $product->brand;
        }

        return [
            'products' => [$productPayload],
        ];
    }
}
