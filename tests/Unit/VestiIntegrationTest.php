<?php

namespace Tests\Unit;

use App\DTOs\ProductData;
use App\DTOs\VariationData;
use App\Mappers\VestiPayloadMapper;
use App\Services\Vesti\VestiClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class VestiIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        putenv('VESTI_API_URL=http://vesti-api');
        putenv('VESTI_API_KEY=test-key');
        putenv('VESTI_COMPANY_ID=00000000-0000-0000-0000-000000000000');
    }

    public function test_vesti_payload_mapper_builds_expected_product_payload(): void
    {
        $mapper = new VestiPayloadMapper();

        $product = new ProductData(
            code: 8750014,
            name: 'SHORT ANTI FIT',
            description: 'Short confortável',
            price: 109.9,
            promotionalPrice: 66.0,
            composition: '100% Algodão',
            brand: 'Joana Modas',
            variations: [
                new VariationData('8750014_G_PRETA', 'G', 'PRETA', 370, 'UN', 3),
                new VariationData('8750014_GG_PRETA', 'GG', 'PRETA', 304, 'UN', 4),
            ],
        );

        $payload = $mapper->map($product);

        $this->assertSame('8750014', $payload['products'][0]['integration_id']);
        $this->assertSame('8750014', $payload['products'][0]['code']);
        $this->assertSame('SHORT ANTI FIT', $payload['products'][0]['name']);
        $this->assertSame('Short confortável', $payload['products'][0]['description']);
        $this->assertSame(109.9, $payload['products'][0]['price']);
        $this->assertTrue($payload['products'][0]['promotion']);
        $this->assertSame(66.0, $payload['products'][0]['price_promotional']);
        $this->assertSame('100% Algodão', $payload['products'][0]['composition']);
        $this->assertSame('Joana Modas', $payload['products'][0]['brand']);
        $this->assertSame('8750014_G_PRETA', $payload['products'][0]['variations'][0]['sku']);
        $this->assertSame('G', $payload['products'][0]['variations'][0]['size']);
        $this->assertSame('PRETA', $payload['products'][0]['variations'][0]['color']);
        $this->assertSame(370, $payload['products'][0]['variations'][0]['quantity']);
        $this->assertSame('UN', $payload['products'][0]['variations'][0]['unit_type']);
        $this->assertSame(3, $payload['products'][0]['variations'][0]['order']);
    }

    public function test_vesti_client_posts_correct_payload_and_headers(): void
    {
        Http::fake([
            'http://vesti-api/v1/products/company/00000000-0000-0000-0000-000000000000' => Http::response([
                'result' => ['success' => true, 'message' => 'Ok', 'messages' => ''],
                'statusCode' => 200,
            ], 200),
        ]);

        $client = new VestiClient();

        $product = new ProductData(
            code: 8750014,
            name: 'SHORT ANTI FIT',
            description: 'Short confortável',
            price: 109.9,
            promotionalPrice: 66.0,
            composition: '100% Algodão',
            brand: 'Joana Modas',
            variations: [
                new VariationData('8750014_G_PRETA', 'G', 'PRETA', 370, 'UN', 3),
            ],
        );

        $response = $client->createProduct($product);

        $this->assertSame(['result' => ['success' => true, 'message' => 'Ok', 'messages' => ''], 'statusCode' => 200], $response);
        Http::assertSent(function ($request) {
            return $request->method() === 'POST'
                && $request->url() === 'http://vesti-api/v1/products/company/00000000-0000-0000-0000-000000000000'
                && $request['products'][0]['integration_id'] === '8750014'
                && $request['products'][0]['variations'][0]['sku'] === '8750014_G_PRETA'
                && $request->hasHeader('apikey', 'test-key')
                && $request->hasHeader('Content-Type', 'application/json');
        });
    }

    public function test_vesti_client_throws_on_http_4xx(): void
    {
        Http::fake([
            'http://vesti-api/v1/products/company/00000000-0000-0000-0000-000000000000' => Http::response(['error' => 'Bad Request'], 400),
        ]);

        $this->expectException(RuntimeException::class);

        (new VestiClient())->createProduct(new ProductData(
            code: 8750014,
            name: 'SHORT ANTI FIT',
            description: null,
            price: 109.9,
            promotionalPrice: null,
            composition: null,
            brand: null,
            variations: []
        ));
    }

    public function test_vesti_client_throws_on_http_5xx(): void
    {
        Http::fake([
            'http://vesti-api/v1/products/company/00000000-0000-0000-0000-000000000000' => Http::response(['error' => 'Server Error'], 500),
        ]);

        $this->expectException(RuntimeException::class);

        (new VestiClient())->createProduct(new ProductData(
            code: 8750014,
            name: 'SHORT ANTI FIT',
            description: null,
            price: 109.9,
            promotionalPrice: null,
            composition: null,
            brand: null,
            variations: []
        ));
    }

    public function test_vesti_client_throws_on_timeout_or_connection_failure(): void
    {
        Http::fake([
            'http://vesti-api/v1/products/company/00000000-0000-0000-0000-000000000000' => fn () => throw new ConnectionException('timeout'),
        ]);

        $this->expectException(RuntimeException::class);

        (new VestiClient())->createProduct(new ProductData(
            code: 8750014,
            name: 'SHORT ANTI FIT',
            description: null,
            price: 109.9,
            promotionalPrice: null,
            composition: null,
            brand: null,
            variations: []
        ));
    }

    public function test_vesti_client_rejects_invalid_json_response(): void
    {
        Http::fake([
            'http://vesti-api/v1/products/company/00000000-0000-0000-0000-000000000000' => Http::response('{invalid-json', 200),
        ]);

        $this->expectException(RuntimeException::class);

        (new VestiClient())->createProduct(new ProductData(
            code: 8750014,
            name: 'SHORT ANTI FIT',
            description: null,
            price: 109.9,
            promotionalPrice: null,
            composition: null,
            brand: null,
            variations: []
        ));
    }
}
