<?php

namespace Tests\Unit;

use App\Contracts\ErpClientInterface;
use App\Contracts\ProductMapperInterface;
use App\Contracts\SalesPlatformInterface;
use App\DTOs\ProductData;
use App\DTOs\VariationData;
use App\Services\ProductSyncService;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class ProductSyncServiceTest extends TestCase
{
    public function test_sync_fetches_products_and_variations_and_sends_each_product_to_sales_platform(): void
    {
        $erpClient = Mockery::mock(ErpClientInterface::class);
        $mapper = Mockery::mock(ProductMapperInterface::class);
        $platform = Mockery::mock(SalesPlatformInterface::class);

        $products = [
            ['code' => 8750014, 'name' => 'Produto 1'],
            ['code' => 1761349, 'name' => 'Produto 2'],
        ];

        $variations = [
            ['sku' => '8750014_G_PRETA', 'size' => 'G', 'color' => 'PRETA', 'quantity' => 10, 'unit_measurement' => 'UN', 'ordering' => 1],
            ['sku' => '8750014_GG_PRETA', 'size' => 'GG', 'color' => 'PRETA', 'quantity' => 5, 'unit_measurement' => 'UN', 'ordering' => 2],
            ['sku' => '1761349_40_ESCURA', 'size' => 40, 'color' => 'ESCURA', 'quantity' => 15, 'unit_measurement' => 'UN', 'ordering' => 1],
        ];

        $erpClient->shouldReceive('getVariations')->once()->andReturn($variations);
        $erpClient->shouldReceive('getProducts')->once()->andReturn($products);

        $mapper->shouldReceive('getProductCodeFromVariation')->withArgs(function (array $variation): bool {
            return $variation['sku'] === '8750014_G_PRETA';
        })->andReturn(8750014);
        $mapper->shouldReceive('getProductCodeFromVariation')->withArgs(function (array $variation): bool {
            return $variation['sku'] === '8750014_GG_PRETA';
        })->andReturn(8750014);
        $mapper->shouldReceive('getProductCodeFromVariation')->withArgs(function (array $variation): bool {
            return $variation['sku'] === '1761349_40_ESCURA';
        })->andReturn(1761349);

        $mapper->shouldReceive('mapProduct')->with(['code' => 8750014, 'name' => 'Produto 1'])->andReturn(
            new ProductData(code: 8750014, name: 'Produto 1', description: null, price: 109.9, promotionalPrice: 66.0, composition: null, brand: null, variations: [])
        );
        $mapper->shouldReceive('mapProduct')->with(['code' => 1761349, 'name' => 'Produto 2'])->andReturn(
            new ProductData(code: 1761349, name: 'Produto 2', description: null, price: 99.9, promotionalPrice: null, composition: null, brand: null, variations: [])
        );

        $mapper->shouldReceive('mapVariation')->with(['sku' => '8750014_G_PRETA', 'size' => 'G', 'color' => 'PRETA', 'quantity' => 10, 'unit_measurement' => 'UN', 'ordering' => 1])->andReturn(new VariationData('8750014_G_PRETA', 'G', 'PRETA', 10, 'UN', 1));
        $mapper->shouldReceive('mapVariation')->with(['sku' => '8750014_GG_PRETA', 'size' => 'GG', 'color' => 'PRETA', 'quantity' => 5, 'unit_measurement' => 'UN', 'ordering' => 2])->andReturn(new VariationData('8750014_GG_PRETA', 'GG', 'PRETA', 5, 'UN', 2));
        $mapper->shouldReceive('mapVariation')->with(['sku' => '1761349_40_ESCURA', 'size' => 40, 'color' => 'ESCURA', 'quantity' => 15, 'unit_measurement' => 'UN', 'ordering' => 1])->andReturn(new VariationData('1761349_40_ESCURA', 40, 'ESCURA', 15, 'UN', 1));

        $platform->shouldReceive('createProduct')->twice();

        $service = new ProductSyncService($erpClient, $mapper, $platform);

        $service->sync();
    }

    public function test_sync_assigns_all_variations_that_match_the_product_code(): void
    {
        $erpClient = Mockery::mock(ErpClientInterface::class);
        $mapper = Mockery::mock(ProductMapperInterface::class);
        $platform = Mockery::mock(SalesPlatformInterface::class);

        $erpClient->shouldReceive('getVariations')->once()->andReturn([
            ['sku' => '8750014_G_PRETA', 'size' => 'G', 'color' => 'PRETA', 'quantity' => 10, 'unit_measurement' => 'UN', 'ordering' => 1],
            ['sku' => '8750014_GG_PRETA', 'size' => 'GG', 'color' => 'PRETA', 'quantity' => 5, 'unit_measurement' => 'UN', 'ordering' => 2],
            ['sku' => '1761349_42_ESCURA', 'size' => 42, 'color' => 'ESCURA', 'quantity' => 9, 'unit_measurement' => 'UN', 'ordering' => 1],
        ]);

        $erpClient->shouldReceive('getProducts')->once()->andReturn([
            ['code' => 8750014, 'name' => 'Produto 1'],
        ]);

        $mapper->shouldReceive('getProductCodeFromVariation')->withArgs(function (array $variation): bool {
            return $variation['sku'] === '8750014_G_PRETA';
        })->andReturn(8750014);
        $mapper->shouldReceive('getProductCodeFromVariation')->withArgs(function (array $variation): bool {
            return $variation['sku'] === '8750014_GG_PRETA';
        })->andReturn(8750014);
        $mapper->shouldReceive('getProductCodeFromVariation')->withArgs(function (array $variation): bool {
            return $variation['sku'] === '1761349_42_ESCURA';
        })->andReturn(1761349);

        $mapper->shouldReceive('mapProduct')->with(['code' => 8750014, 'name' => 'Produto 1'])->andReturn(
            new ProductData(code: 8750014, name: 'Produto 1', description: null, price: 120.0, promotionalPrice: null, composition: null, brand: null, variations: [])
        );

        $mapper->shouldReceive('mapVariation')->with(['sku' => '8750014_G_PRETA', 'size' => 'G', 'color' => 'PRETA', 'quantity' => 10, 'unit_measurement' => 'UN', 'ordering' => 1])->andReturn(new VariationData('8750014_G_PRETA', 'G', 'PRETA', 10, 'UN', 1));
        $mapper->shouldReceive('mapVariation')->with(['sku' => '8750014_GG_PRETA', 'size' => 'GG', 'color' => 'PRETA', 'quantity' => 5, 'unit_measurement' => 'UN', 'ordering' => 2])->andReturn(new VariationData('8750014_GG_PRETA', 'GG', 'PRETA', 5, 'UN', 2));

        $platform = Mockery::mock(SalesPlatformInterface::class);
        $platform->shouldReceive('createProduct')->once()->with(Mockery::on(function (ProductData $product): bool {
            return $product->code === 8750014 && count($product->variations) === 2;
        }))->andReturn(['ok' => true]);

        $service = new ProductSyncService($erpClient, $mapper, $platform);
        $service->sync();
    }

    public function test_sync_sends_product_with_empty_variation_list_when_none_are_found(): void
    {
        $erpClient = Mockery::mock(ErpClientInterface::class);
        $mapper = Mockery::mock(ProductMapperInterface::class);
        $platform = Mockery::mock(SalesPlatformInterface::class);

        $erpClient->shouldReceive('getVariations')->once()->andReturn([
            ['sku' => '9999999_X_Y', 'size' => 'X', 'color' => 'Y', 'quantity' => 2, 'unit_measurement' => 'UN', 'ordering' => 1],
        ]);
        $erpClient->shouldReceive('getProducts')->once()->andReturn([
            ['code' => 8750014, 'name' => 'Produto sem variação'],
        ]);

        $mapper->shouldReceive('getProductCodeFromVariation')->withArgs(function (array $variation): bool {
            return $variation['sku'] === '9999999_X_Y';
        })->andReturn(9999999);
        $mapper->shouldReceive('mapProduct')->with(['code' => 8750014, 'name' => 'Produto sem variação'])->andReturn(
            new ProductData(code: 8750014, name: 'Produto sem variação', description: null, price: 10.0, promotionalPrice: null, composition: null, brand: null, variations: [])
        );

        $platform->shouldReceive('createProduct')->once()->with(Mockery::on(function (ProductData $product): bool {
            return $product->code === 8750014 && $product->variations === [];
        }))->andReturn(['ok' => true]);

        $service = new ProductSyncService($erpClient, $mapper, $platform);
        $service->sync();
    }

    public function test_sync_ignores_variations_without_matching_product(): void
    {
        $erpClient = Mockery::mock(ErpClientInterface::class);
        $mapper = Mockery::mock(ProductMapperInterface::class);
        $platform = Mockery::mock(SalesPlatformInterface::class);

        $erpClient->shouldReceive('getVariations')->once()->andReturn([
            ['sku' => '9999999_X_Y', 'size' => 'X', 'color' => 'Y', 'quantity' => 2, 'unit_measurement' => 'UN', 'ordering' => 1],
        ]);
        $erpClient->shouldReceive('getProducts')->once()->andReturn([
            ['code' => 8750014, 'name' => 'Produto existente'],
        ]);

        $mapper->shouldReceive('getProductCodeFromVariation')->withArgs(function (array $variation): bool {
            return $variation['sku'] === '9999999_X_Y';
        })->andReturn(9999999);
        $mapper->shouldReceive('mapProduct')->with(['code' => 8750014, 'name' => 'Produto existente'])->andReturn(
            new ProductData(code: 8750014, name: 'Produto existente', description: null, price: 10.0, promotionalPrice: null, composition: null, brand: null, variations: [])
        );

        $platform->shouldReceive('createProduct')->once()->with(Mockery::on(function (ProductData $product): bool {
            return $product->code === 8750014 && $product->variations === [];
        }))->andReturn(['ok' => true]);

        $service = new ProductSyncService($erpClient, $mapper, $platform);
        $service->sync();
    }

    public function test_sync_throws_when_erp_client_fails(): void
    {
        $this->expectException(RuntimeException::class);

        $erpClient = Mockery::mock(ErpClientInterface::class);
        $mapper = Mockery::mock(ProductMapperInterface::class);
        $platform = Mockery::mock(SalesPlatformInterface::class);

        $erpClient->shouldReceive('getVariations')->once()->andThrow(new RuntimeException('ERP unavailable'));

        $service = new ProductSyncService($erpClient, $mapper, $platform);
        $service->sync();
    }

    public function test_sync_throws_when_mapper_fails(): void
    {
        $this->expectException(RuntimeException::class);

        $erpClient = Mockery::mock(ErpClientInterface::class);
        $mapper = Mockery::mock(ProductMapperInterface::class);
        $platform = Mockery::mock(SalesPlatformInterface::class);

        $erpClient->shouldReceive('getVariations')->once()->andReturn([
            ['sku' => '8750014_G_PRETA', 'size' => 'G', 'color' => 'PRETA', 'quantity' => 10, 'unit_measurement' => 'UN', 'ordering' => 1],
        ]);
        $erpClient->shouldReceive('getProducts')->once()->andReturn([
            ['code' => 8750014, 'name' => 'Produto 1'],
        ]);

        $mapper->shouldReceive('getProductCodeFromVariation')->withArgs(function (array $variation): bool {
            return $variation['sku'] === '8750014_G_PRETA';
        })->andReturn(8750014);
        $mapper->shouldReceive('mapProduct')->with(['code' => 8750014, 'name' => 'Produto 1'])->andThrow(new RuntimeException('Invalid product'));

        $service = new ProductSyncService($erpClient, $mapper, $platform);
        $service->sync();
    }

    public function test_sync_throws_when_destination_platform_fails(): void
    {
        $this->expectException(RuntimeException::class);

        $erpClient = Mockery::mock(ErpClientInterface::class);
        $mapper = Mockery::mock(ProductMapperInterface::class);
        $platform = Mockery::mock(SalesPlatformInterface::class);

        $erpClient->shouldReceive('getVariations')->once()->andReturn([]);
        $erpClient->shouldReceive('getProducts')->once()->andReturn([
            ['code' => 8750014, 'name' => 'Produto 1'],
        ]);

        $mapper->shouldReceive('mapProduct')->with(['code' => 8750014, 'name' => 'Produto 1'])->andReturn(
            new ProductData(code: 8750014, name: 'Produto 1', description: null, price: 10.0, promotionalPrice: null, composition: null, brand: null, variations: [])
        );

        $platform->shouldReceive('createProduct')->once()->andThrow(new RuntimeException('Platform failed'));

        $service = new ProductSyncService($erpClient, $mapper, $platform);
        $service->sync();
    }
}
