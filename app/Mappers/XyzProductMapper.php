<?php

namespace App\Mappers;

use App\Contracts\ProductMapperInterface;
use App\DTOs\ProductData;
use App\DTOs\VariationData;
use InvalidArgumentException;

class XyzProductMapper implements ProductMapperInterface
{
    public function mapProduct(array $product): ProductData
    {
        $code = $this->requireInt($product, 'referencia');
        $name = $this->requireString($product, 'nome');
        $description = $this->nullableString($product, 'descricao');
        $price = $this->parsePrice($product['preco'] ?? null, 'preco');
        $promotionalPrice = array_key_exists('promocao', $product) && $product['promocao'] !== null
            ? $this->parsePrice($product['promocao'], 'promocao')
            : null;
        $composition = $this->nullableString($product, 'composicao');
        $brand = $this->nullableString($product, 'marca');

        return new ProductData(
            code: $code,
            name: $name,
            description: $description,
            price: $price,
            promotionalPrice: $promotionalPrice,
            composition: $composition,
            brand: $brand,
            variations: [],
        );
    }

    public function mapVariation(array $variation): VariationData
    {
        $sku = $this->requireString($variation, 'variacao');
        $size = $variation['tamanho'] ?? null;
        $color = $this->requireString($variation, 'cor');
        $quantity = $this->requireInt($variation, 'quantidade');
        $unitMeasurement = $this->requireString($variation, 'unidade');
        $ordering = $this->requireInt($variation, 'ordem');

        if ($size === null || $size === '') {
            throw new InvalidArgumentException('Variation size is required.');
        }

        $normalizedSize = is_numeric($size) ? (int) $size : (string) $size;

        return new VariationData(
            sku: $sku,
            size: $normalizedSize,
            color: $color,
            quantity: $quantity,
            unitMeasurement: $unitMeasurement,
            ordering: $ordering,
        );
    }

    public function getProductCodeFromVariation(array $variation): int
    {
        $sku = $this->requireString($variation, 'variacao');

        $parts = explode('_', $sku);

        if (count($parts) < 2 || $parts[0] === '' || ! is_numeric($parts[0])) {
            throw new InvalidArgumentException('Invalid variation SKU. Product code could not be extracted.');
        }

        return (int) $parts[0];
    }

    private function requireInt(array $data, string $key): int
    {
        if (! array_key_exists($key, $data) || $data[$key] === null || $data[$key] === '') {
            throw new InvalidArgumentException(sprintf('Required field "%s" is missing or empty.', $key));
        }

        if (is_int($data[$key])) {
            return $data[$key];
        }

        if (is_numeric($data[$key])) {
            return (int) $data[$key];
        }

        throw new InvalidArgumentException(sprintf('Field "%s" must be numeric.', $key));
    }

    private function requireString(array $data, string $key): string
    {
        if (! array_key_exists($key, $data) || $data[$key] === null) {
            throw new InvalidArgumentException(sprintf('Required field "%s" is missing.', $key));
        }

        if (is_string($data[$key])) {
            return $data[$key];
        }

        throw new InvalidArgumentException(sprintf('Field "%s" must be a string.', $key));
    }

    private function nullableString(array $data, string $key): ?string
    {
        if (! array_key_exists($key, $data) || $data[$key] === null) {
            return null;
        }

        return is_string($data[$key]) ? $data[$key] : throw new InvalidArgumentException(sprintf('Field "%s" must be a string or null.', $key));
    }

    private function parsePrice(mixed $value, string $field): float
    {
        if ($value === null || $value === '') {
            throw new InvalidArgumentException(sprintf('Field "%s" is required and cannot be empty.', $field));
        }

        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        if (! is_string($value)) {
            throw new InvalidArgumentException(sprintf('Field "%s" must be numeric or a string price.', $field));
        }

        $normalized = trim($value);

        if ($normalized === '') {
            throw new InvalidArgumentException(sprintf('Field "%s" is required and cannot be empty.', $field));
        }

        $normalized = str_replace('.', '', $normalized);
        $normalized = str_replace(',', '.', $normalized);

        if (! is_numeric($normalized)) {
            throw new InvalidArgumentException(sprintf('Field "%s" has an invalid price format.', $field));
        }

        return (float) $normalized;
    }
}
