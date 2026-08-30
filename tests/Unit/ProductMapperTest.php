<?php

namespace Tests\Unit;

use App\Mappers\XptoProductMapper;
use App\Mappers\XyzProductMapper;
use App\DTOs\ProductData;
use App\DTOs\VariationData;
use InvalidArgumentException;
use Tests\TestCase;

class ProductMapperTest extends TestCase
{
    public function test_xpto_product_mapping(): void
    {
        $mapper = new XptoProductMapper();

        $product = [
            'code' => 1761095,
            'name' => 'SHORT ANTI FIT',
            'description' => null,
            'price' => '109,90',
            'price_promotional' => 66,
            'composition' => '100% Algodão',
            'brand' => 'Joana Modas',
        ];

        $result = $mapper->mapProduct($product);

        $this->assertInstanceOf(ProductData::class, $result);
        $this->assertSame(1761095, $result->code);
        $this->assertSame('SHORT ANTI FIT', $result->name);
        $this->assertSame(null, $result->description);
        $this->assertSame(109.9, $result->price);
        $this->assertSame(66.0, $result->promotionalPrice);
        $this->assertSame('100% Algodão', $result->composition);
        $this->assertSame('Joana Modas', $result->brand);
    }

    public function test_xpto_variation_mapping(): void
    {
        $mapper = new XptoProductMapper();

        $variation = [
            'sku' => '8750014_G_PRETA',
            'size' => 'G',
            'color' => 'PRETA',
            'quantity' => 370,
            'unit_measurement' => 'UN',
            'ordering' => 3,
        ];

        $result = $mapper->mapVariation($variation);

        $this->assertInstanceOf(VariationData::class, $result);
        $this->assertSame('8750014_G_PRETA', $result->sku);
        $this->assertSame('G', $result->size);
        $this->assertSame('PRETA', $result->color);
        $this->assertSame(370, $result->quantity);
        $this->assertSame('UN', $result->unitMeasurement);
        $this->assertSame(3, $result->ordering);
    }

    public function test_xpto_extracts_product_code_from_variation_sku(): void
    {
        $mapper = new XptoProductMapper();

        $this->assertSame(8750014, $mapper->getProductCodeFromVariation(['sku' => '8750014_G_PRETA']));
        $this->assertSame(8750014, $mapper->getProductCodeFromVariation(['sku' => '8750014_GG_PRETA']));
    }

    public function test_xyz_product_mapping(): void
    {
        $mapper = new XyzProductMapper();

        $product = [
            'referencia' => 1761095,
            'nome' => 'SHORT ANTI FIT',
            'descricao' => null,
            'preco' => '109,90',
            'promocao' => 66,
            'composicao' => '100% Algodão',
            'marca' => 'Joana Modas',
        ];

        $result = $mapper->mapProduct($product);

        $this->assertInstanceOf(ProductData::class, $result);
        $this->assertSame(1761095, $result->code);
        $this->assertSame('SHORT ANTI FIT', $result->name);
        $this->assertSame(null, $result->description);
        $this->assertSame(109.9, $result->price);
        $this->assertSame(66.0, $result->promotionalPrice);
        $this->assertSame('100% Algodão', $result->composition);
        $this->assertSame('Joana Modas', $result->brand);
    }

    public function test_xyz_variation_mapping(): void
    {
        $mapper = new XyzProductMapper();

        $variation = [
            'variacao' => '8750014_G_PRETA',
            'tamanho' => 'G',
            'cor' => 'PRETA',
            'quantidade' => 370,
            'unidade' => 'UN',
            'ordem' => 3,
        ];

        $result = $mapper->mapVariation($variation);

        $this->assertInstanceOf(VariationData::class, $result);
        $this->assertSame('8750014_G_PRETA', $result->sku);
        $this->assertSame('G', $result->size);
        $this->assertSame('PRETA', $result->color);
        $this->assertSame(370, $result->quantity);
        $this->assertSame('UN', $result->unitMeasurement);
        $this->assertSame(3, $result->ordering);
    }

    public function test_xyz_extracts_product_code_from_variation_sku(): void
    {
        $mapper = new XyzProductMapper();

        $this->assertSame(8750014, $mapper->getProductCodeFromVariation(['variacao' => '8750014_G_PRETA']));
        $this->assertSame(8750014, $mapper->getProductCodeFromVariation(['variacao' => '8750014_GG_PRETA']));
    }

    public function test_xpto_mapper_requires_required_fields(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new XptoProductMapper())->mapProduct(['name' => 'SHORT ANTI FIT']);
    }

    public function test_xpto_mapper_requires_valid_sku_for_product_code(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new XptoProductMapper())->getProductCodeFromVariation(['sku' => 'INVALID']);
    }

    public function test_xyz_mapper_requires_valid_variation_structure(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new XyzProductMapper())->mapVariation(['cor' => 'PRETA']);
    }

    public function test_price_conversion_works_for_comma_decimals(): void
    {
        $mapper = new XptoProductMapper();

        $product = [
            'code' => 1761095,
            'name' => 'SHORT ANTI FIT',
            'description' => null,
            'price' => '109,90',
            'price_promotional' => 66,
            'composition' => '100% Algodão',
            'brand' => 'Joana Modas',
        ];

        $this->assertSame(109.9, $mapper->mapProduct($product)->price);
    }

    public function test_invalid_price_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new XptoProductMapper())->mapProduct([
            'code' => 1761095,
            'name' => 'SHORT ANTI FIT',
            'description' => null,
            'price' => 'invalid',
            'price_promotional' => 66,
            'composition' => '100% Algodão',
            'brand' => 'Joana Modas',
        ]);
    }
}
