<?php

namespace App\DTOs;

final readonly class ProductData
{
    /**
     * @param array<int, VariationData> $variations
     */
    public function __construct(
        public int $code,
        public string $name,
        public ?string $description,
        public float $price,
        public ?float $promotionalPrice,
        public ?string $composition,
        public ?string $brand,
        public array $variations,
    ) {
    }
}
