<?php

namespace Tests\Unit;

use App\Services\Erp\XptoErpClient;
use App\Services\Erp\XyzErpClient;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class ErpClientsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        putenv('ERP_XPTO_API_URL=http://erp-mock');
        putenv('ERP_XYZ_API_URL=http://erp-mock');
    }

    public function test_xpto_get_products_uses_expected_url_and_returns_payload(): void
    {
        Http::fake([
            'http://erp-mock/erp/xpto/produtos.json' => Http::response([
                ['code' => 1761095, 'name' => 'SHORT ANTI FIT'],
            ], 200),
        ]);

        $client = new XptoErpClient();

        $result = $client->getProducts();

        $this->assertSame([['code' => 1761095, 'name' => 'SHORT ANTI FIT']], $result);
        Http::assertSent(fn ($request) => $request->method() === 'GET'
            && $request->url() === 'http://erp-mock/erp/xpto/produtos.json');
    }

    public function test_xpto_get_variations_uses_expected_url_and_returns_payload(): void
    {
        Http::fake([
            'http://erp-mock/erp/xpto/variacoes.json' => Http::response([
                ['sku' => '8750014_G_PRETA', 'size' => 'G'],
            ], 200),
        ]);

        $client = new XptoErpClient();

        $result = $client->getVariations();

        $this->assertSame([['sku' => '8750014_G_PRETA', 'size' => 'G']], $result);
        Http::assertSent(fn ($request) => $request->method() === 'GET'
            && $request->url() === 'http://erp-mock/erp/xpto/variacoes.json');
    }

    public function test_xyz_get_products_uses_expected_url_and_returns_payload(): void
    {
        Http::fake([
            'http://erp-mock/erp/xyz/produtos.json' => Http::response([
                ['referencia' => 1761095, 'nome' => 'SHORT ANTI FIT'],
            ], 200),
        ]);

        $client = new XyzErpClient();

        $result = $client->getProducts();

        $this->assertSame([['referencia' => 1761095, 'nome' => 'SHORT ANTI FIT']], $result);
        Http::assertSent(fn ($request) => $request->method() === 'GET'
            && $request->url() === 'http://erp-mock/erp/xyz/produtos.json');
    }

    public function test_xyz_get_variations_uses_expected_url_and_returns_payload(): void
    {
        Http::fake([
            'http://erp-mock/erp/xyz/variacoes.json' => Http::response([
                ['variacao' => '8750014_G_PRETA', 'tamanho' => 'G'],
            ], 200),
        ]);

        $client = new XyzErpClient();

        $result = $client->getVariations();

        $this->assertSame([['variacao' => '8750014_G_PRETA', 'tamanho' => 'G']], $result);
        Http::assertSent(fn ($request) => $request->method() === 'GET'
            && $request->url() === 'http://erp-mock/erp/xyz/variacoes.json');
    }

    public function test_xpto_get_products_throws_on_http_4xx(): void
    {
        Http::fake([
            'http://erp-mock/erp/xpto/produtos.json' => Http::response(['error' => 'Bad Request'], 400),
        ]);

        $this->expectException(RuntimeException::class);

        (new XptoErpClient())->getProducts();
    }

    public function test_xpto_get_products_throws_on_http_5xx(): void
    {
        Http::fake([
            'http://erp-mock/erp/xpto/produtos.json' => Http::response(['error' => 'Internal Server Error'], 500),
        ]);

        $this->expectException(RuntimeException::class);

        (new XptoErpClient())->getProducts();
    }

    public function test_xpto_get_products_throws_on_timeout_or_connection_failure(): void
    {
        Http::fake([
            'http://erp-mock/erp/xpto/produtos.json' => fn () => throw new \Illuminate\Http\Client\ConnectionException('timeout'),
        ]);

        $this->expectException(RuntimeException::class);

        (new XptoErpClient())->getProducts();
    }

    public function test_xpto_get_products_throws_on_invalid_json(): void
    {
        Http::fake([
            'http://erp-mock/erp/xpto/produtos.json' => Http::response('{invalid-json', 200),
        ]);

        $this->expectException(RuntimeException::class);

        (new XptoErpClient())->getProducts();
    }
}
