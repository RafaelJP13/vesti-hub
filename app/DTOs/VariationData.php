<?php

namespace App\DTOs;

final readonly class VariationData
{
    public function __construct(
        public string $sku,
        public string|int $size,
        public string $color,
        public int $quantity,
        public string $unitMeasurement,
        public int $ordering,
    ) {
    }
}
