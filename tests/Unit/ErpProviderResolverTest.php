<?php

namespace Tests\Unit;

use App\Console\Commands\SyncProductsCommand;
use App\Contracts\ErpClientInterface;
use App\Contracts\ProductMapperInterface;
use App\Services\Erp\ErpProviderResolver;
use App\Services\Erp\XptoErpClient;
use App\Services\Erp\XyzErpClient;
use App\Services\ProductSyncService;
use App\Mappers\XptoProductMapper;
use App\Mappers\XyzProductMapper;
use App\Services\Vesti\VestiClient;
use Illuminate\Support\Facades\Http;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class ErpProviderResolverTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        putenv('ERP_PROVIDER=xpto');
        putenv('ERP_XPTO_API_URL=http://erp-mock');
        putenv('ERP_XYZ_API_URL=http://erp-mock');
        putenv('VESTI_API_URL=http://vesti-api');
        putenv('VESTI_API_KEY=test-key');
        putenv('VESTI_COMPANY_ID=11111111-2222-3333-4444-555555666666');
    }

    public function test_xpto_provider_resolves_xpto_client_and_mapper(): void
    {
        $resolver = new ErpProviderResolver();

        $this->assertInstanceOf(XptoErpClient::class, $resolver->resolveClient());
        $this->assertInstanceOf(XptoProductMapper::class, $resolver->resolveMapper());
    }

    public function test_xyz_provider_resolves_xyz_client_and_mapper(): void
    {
        putenv('ERP_PROVIDER=xyz');

        $resolver = new ErpProviderResolver();

        $this->assertInstanceOf(XyzErpClient::class, $resolver->resolveClient());
        $this->assertInstanceOf(XyzProductMapper::class, $resolver->resolveMapper());
    }

    public function test_invalid_provider_throws_clear_error(): void
    {
        putenv('ERP_PROVIDER=invalid');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported ERP provider');

        (new ErpProviderResolver())->resolveClient();
    }

    public function test_service_container_binds_interfaces_using_configured_provider(): void
    {
        putenv('ERP_PROVIDER=xyz');

        $this->assertInstanceOf(XyzErpClient::class, $this->app->make(ErpClientInterface::class));
        $this->assertInstanceOf(XyzProductMapper::class, $this->app->make(ProductMapperInterface::class));
    }

    public function test_command_executes_product_sync_service(): void
    {
        $service = Mockery::mock(ProductSyncService::class);
        $service->shouldReceive('sync')->once()->andReturn([['ok' => true]]);

        $this->app->instance(ProductSyncService::class, $service);

        $this->artisan('products:sync')->assertSuccessful();
    }

    public function test_full_erp_to_vesti_flow_works_with_http_fake(): void
    {
        Http::fake([
            'http://erp-mock:8080/erp/xpto/produtos.json' => Http::response([
                ['code' => 8750014, 'name' => 'SHORT ANTI FIT', 'description' => null, 'price' => '109,90', 'price_promotional' => 66, 'composition' => '100% Algodão', 'brand' => 'Joana Modas'],
                ['code' => 8750015, 'name' => 'CAMISA TESTE', 'description' => null, 'price' => '89,90', 'price_promotional' => 55, 'composition' => '100% Linho', 'brand' => 'Joana Modas'],
            ], 200),
            'http://erp-mock:8080/erp/xpto/variacoes.json' => Http::response([
                ['sku' => '8750014_G_PRETA', 'size' => 'G', 'color' => 'PRETA', 'quantity' => 370, 'unit_measurement' => 'UN', 'ordering' => 3],
                ['sku' => '8750015_M_AZUL', 'size' => 'M', 'color' => 'AZUL', 'quantity' => 120, 'unit_measurement' => 'UN', 'ordering' => 2],
            ], 200),
            'http://vesti-api/v1/products/company/11111111-2222-3333-4444-555555666666' => Http::response([
                'result' => ['success' => true, 'message' => 'Ok', 'messages' => ''],
                'statusCode' => 200,
            ], 200),
        ]);

        $service = new ProductSyncService(
            (new ErpProviderResolver())->resolveClient(),
            (new ErpProviderResolver())->resolveMapper(),
            new VestiClient(),
        );

        $results = $service->sync();

        $this->assertIsArray($results);
        $this->assertNotEmpty($results);
        $this->assertSame('Ok', $results[0]['result']['message']);
        $this->assertSame('Ok', $results[1]['result']['message']);

        Http::assertSent(function ($request) {
            return $request->method() === 'POST'
                && $request->url() === 'http://vesti-api/v1/products/company/11111111-2222-3333-4444-555555666666'
                && is_array($request['products'])
                && $request['products'][0]['integration_id'] === '8750014'
                && $request['products'][0]['variations'][0]['sku'] === '8750014_G_PRETA';
        });
        Http::assertSent(function ($request) {
            return $request->method() === 'POST'
                && $request->url() === 'http://vesti-api/v1/products/company/11111111-2222-3333-4444-555555666666'
                && is_array($request['products'])
                && $request['products'][0]['integration_id'] === '8750015'
                && $request['products'][0]['variations'][0]['sku'] === '8750015_M_AZUL';
        });
    }
}
