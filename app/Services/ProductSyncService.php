<?php

namespace App\Services;

use App\Contracts\ErpClientInterface;
use App\Contracts\ProductMapperInterface;
use App\Contracts\SalesPlatformInterface;
use App\DTOs\ProductData;
use App\DTOs\VariationData;
use RuntimeException;

class ProductSyncService
{
    public function __construct(
        private readonly ErpClientInterface $erpClient,
        private readonly ProductMapperInterface $mapper,
        private readonly SalesPlatformInterface $salesPlatform,
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function sync(): array
    {
        try {
            $variations = $this->erpClient->getVariations();
            $variationsByProductCode = $this->indexVariations($variations);

            $products = $this->erpClient->getProducts();
            $results = [];

            foreach ($products as $productData) {
                $productCode = $this->extractProductCodeFromProduct($productData);
                $mappedProduct = $this->mapper->mapProduct($productData);

                $mappedVariations = [];
                foreach ($variationsByProductCode[$productCode] ?? [] as $variation) {
                    $mappedVariations[] = $this->mapper->mapVariation($variation);
                }

                $productWithVariations = new ProductData(
                    code: $mappedProduct->code,
                    name: $mappedProduct->name,
                    description: $mappedProduct->description,
                    price: $mappedProduct->price,
                    promotionalPrice: $mappedProduct->promotionalPrice,
                    composition: $mappedProduct->composition,
                    brand: $mappedProduct->brand,
                    variations: $mappedVariations,
                );

                $results[] = $this->salesPlatform->createProduct($productWithVariations);
            }

            return $results;
        } catch (\Throwable $exception) {
            throw new RuntimeException('Product synchronization failed: ' . $exception->getMessage(), 0, $exception);
        }
    }

    /**
     * @param array<int, array<string, mixed>> $variations
     * @return array<int, array<int, array<string, mixed>>>
     */
    private function indexVariations(array $variations): array
    {
        $indexed = [];

        foreach ($variations as $variation) {
            $productCode = $this->mapper->getProductCodeFromVariation($variation);
            $indexed[$productCode][] = $variation;
        }

        return $indexed;
    }

    /**
     * @param array<string, mixed> $product
     */
    private function extractProductCodeFromProduct(array $product): int
    {
        if (array_key_exists('code', $product)) {
            return (int) $product['code'];
        }

        if (array_key_exists('referencia', $product)) {
            return (int) $product['referencia'];
        }

        throw new RuntimeException('Product code not found in product payload.');
    }
}
