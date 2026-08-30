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

        putenv('ERP_XPTO_API_URL=http://erp-mock:8080');
        putenv('ERP_XYZ_API_URL=http://erp-mock:8080');
    }

    public function test_xpto_get_products_uses_expected_url_and_returns_payload(): void
    {
        $payload = [
            ['code' => 1761095, 'name' => 'SHORT ANTI FIT', 'description' => null, 'price' => '109,90'],
            ['code' => 1761096, 'name' => 'CAMISA TESTE', 'description' => null, 'price' => '89,90'],
        ];

        Http::fake([
            'http://erp-mock:8080/erp/xpto/produtos.json' => Http::response($payload, 200),
        ]);

        $client = new XptoErpClient();

        $result = $client->getProducts();

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertArrayHasKey('code', $result[0]);
        $this->assertArrayHasKey('name', $result[0]);
        $this->assertIsInt($result[0]['code']);
        $this->assertIsString($result[0]['name']);
        Http::assertSent(fn ($request) => $request->method() === 'GET'
            && $request->url() === 'http://erp-mock:8080/erp/xpto/produtos.json');
    }

    public function test_xpto_get_variations_uses_expected_url_and_returns_payload(): void
    {
        $payload = [
            ['sku' => '8750014_G_PRETA', 'size' => 'G', 'color' => 'PRETA', 'quantity' => 370, 'unit_measurement' => 'UN', 'ordering' => 3],
            ['sku' => '8750015_M_AZUL', 'size' => 'M', 'color' => 'AZUL', 'quantity' => 120, 'unit_measurement' => 'UN', 'ordering' => 2],
        ];

        Http::fake([
            'http://erp-mock:8080/erp/xpto/variacoes.json' => Http::response($payload, 200),
        ]);

        $client = new XptoErpClient();

        $result = $client->getVariations();

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertArrayHasKey('sku', $result[0]);
        $this->assertArrayHasKey('size', $result[0]);
        $this->assertArrayHasKey('color', $result[0]);
        $this->assertArrayHasKey('quantity', $result[0]);
        $this->assertArrayHasKey('unit_measurement', $result[0]);
        $this->assertArrayHasKey('ordering', $result[0]);
        $this->assertIsString($result[0]['sku']);
        $this->assertIsString($result[0]['size']);
        $this->assertIsString($result[0]['color']);
        $this->assertIsInt($result[0]['quantity']);
        $this->assertIsString($result[0]['unit_measurement']);
        $this->assertIsInt($result[0]['ordering']);
        Http::assertSent(fn ($request) => $request->method() === 'GET'
            && $request->url() === 'http://erp-mock:8080/erp/xpto/variacoes.json');
    }

    public function test_xyz_get_products_uses_expected_url_and_returns_payload(): void
    {
        $payload = [
            ['referencia' => 1761095, 'nome' => 'SHORT ANTI FIT', 'descricao' => null, 'preco' => '109,90'],
            ['referencia' => 1761096, 'nome' => 'CAMISA TESTE', 'descricao' => null, 'preco' => '89,90'],
        ];

        Http::fake([
            'http://erp-mock:8080/erp/xyz/produtos.json' => Http::response($payload, 200),
        ]);

        $client = new XyzErpClient();

        $result = $client->getProducts();

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertArrayHasKey('referencia', $result[0]);
        $this->assertArrayHasKey('nome', $result[0]);
        $this->assertIsInt($result[0]['referencia']);
        $this->assertIsString($result[0]['nome']);
        Http::assertSent(fn ($request) => $request->method() === 'GET'
            && $request->url() === 'http://erp-mock:8080/erp/xyz/produtos.json');
    }

    public function test_xyz_get_variations_uses_expected_url_and_returns_payload(): void
    {
        $payload = [
            ['variacao' => '8750014_G_PRETA', 'tamanho' => 'G', 'cor' => 'PRETA', 'quantidade' => 370, 'unidade' => 'UN', 'ordem' => 3],
            ['variacao' => '8750015_M_AZUL', 'tamanho' => 'M', 'cor' => 'AZUL', 'quantidade' => 120, 'unidade' => 'UN', 'ordem' => 2],
        ];

        Http::fake([
            'http://erp-mock:8080/erp/xyz/variacoes.json' => Http::response($payload, 200),
        ]);

        $client = new XyzErpClient();

        $result = $client->getVariations();

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertArrayHasKey('variacao', $result[0]);
        $this->assertArrayHasKey('tamanho', $result[0]);
        $this->assertArrayHasKey('cor', $result[0]);
        $this->assertArrayHasKey('quantidade', $result[0]);
        $this->assertArrayHasKey('unidade', $result[0]);
        $this->assertArrayHasKey('ordem', $result[0]);
        $this->assertIsString($result[0]['variacao']);
        $this->assertIsString($result[0]['tamanho']);
        $this->assertIsString($result[0]['cor']);
        $this->assertIsInt($result[0]['quantidade']);
        $this->assertIsString($result[0]['unidade']);
        $this->assertIsInt($result[0]['ordem']);
        Http::assertSent(fn ($request) => $request->method() === 'GET'
            && $request->url() === 'http://erp-mock:8080/erp/xyz/variacoes.json');
    }

    public function test_xpto_get_products_throws_on_http_4xx(): void
    {
        Http::fake([
            'http://erp-mock:8080/erp/xpto/produtos.json' => Http::response(['error' => 'Bad Request'], 400),
        ]);

        $this->expectException(RuntimeException::class);

        (new XptoErpClient())->getProducts();
    }

    public function test_xpto_get_products_throws_on_http_5xx(): void
    {
        Http::fake([
            'http://erp-mock:8080/erp/xpto/produtos.json' => Http::response(['error' => 'Internal Server Error'], 500),
        ]);

        $this->expectException(RuntimeException::class);

        (new XptoErpClient())->getProducts();
    }

    public function test_xpto_get_products_throws_on_timeout_or_connection_failure(): void
    {
        Http::fake([
            'http://erp-mock:8080/erp/xpto/produtos.json' => fn () => throw new \Illuminate\Http\Client\ConnectionException('timeout'),
        ]);

        $this->expectException(RuntimeException::class);

        (new XptoErpClient())->getProducts();
    }

    public function test_xpto_get_products_throws_on_invalid_json(): void
    {
        Http::fake([
            'http://erp-mock:8080/erp/xpto/produtos.json' => Http::response('{invalid-json', 200),
        ]);

        $this->expectException(RuntimeException::class);

        (new XptoErpClient())->getProducts();
    }
}
