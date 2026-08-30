<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ErpMockEndpointsTest extends TestCase
{
    public function test_xpto_products_endpoint_returns_expected_json(): void
    {
        $response = Http::get('http://localhost:8080/erp/xpto/produtos.json');

        $response->throw();
        $this->assertSame(200, $response->status());
        $this->assertSame(1761095, $response->json()[0]['code']);
        $this->assertSame('SHORT ANTI FIT', $response->json()[0]['name']);
        $this->assertSame('Joana Modas', $response->json()[1]['brand']);
    }

    public function test_xpto_variations_endpoint_returns_expected_json(): void
    {
        $response = Http::get('http://localhost:8080/erp/xpto/variacoes.json');

        $response->throw();
        $this->assertSame(200, $response->status());
        $this->assertSame('8750014_G_PRETA', $response->json()[0]['sku']);
        $this->assertSame('G', $response->json()[0]['size']);
        $this->assertSame(370, $response->json()[0]['quantity']);
    }

    public function test_xyz_products_endpoint_returns_expected_json(): void
    {
        $response = Http::get('http://localhost:8080/erp/xyz/produtos.json');

        $response->throw();
        $this->assertSame(200, $response->status());
        $this->assertSame(1761095, $response->json()[0]['referencia']);
        $this->assertSame('SHORT ANTI FIT', $response->json()[0]['nome']);
        $this->assertSame('Joana Modas', $response->json()[1]['marca']);
    }

    public function test_xyz_variations_endpoint_returns_expected_json(): void
    {
        $response = Http::get('http://localhost:8080/erp/xyz/variacoes.json');

        $response->throw();
        $this->assertSame(200, $response->status());
        $this->assertSame('8750014_G_PRETA', $response->json()[0]['variacao']);
        $this->assertSame('G', $response->json()[0]['tamanho']);
        $this->assertSame(370, $response->json()[0]['quantidade']);
    }
}
