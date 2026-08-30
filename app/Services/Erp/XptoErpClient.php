<?php

namespace App\Services\Erp;

use App\Contracts\ErpClientInterface;
use Illuminate\Support\Facades\Http;
use JsonException;
use RuntimeException;

class XptoErpClient implements ErpClientInterface
{
    public function getProducts(): array
    {
        return $this->fetchJson($this->baseUrl() . '/erp/xpto/produtos.json');
    }

    public function getVariations(): array
    {
        return $this->fetchJson($this->baseUrl() . '/erp/xpto/variacoes.json');
    }

    private function baseUrl(): string
    {
        return rtrim((string) env('ERP_XPTO_API_URL', 'http://erp-mock'), '/');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchJson(string $url): array
    {
        try {
            $response = Http::timeout(10)
                ->acceptJson()
                ->get($url);

            if (! $response->successful()) {
                throw new RuntimeException(sprintf('ERP XPTO request failed with status %d: %s', $response->status(), $response->body()));
            }

            try {
                return json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                throw new RuntimeException('ERP XPTO response is not valid JSON.', 0, $e);
            }
        } catch (\Throwable $e) {
            if ($e instanceof RuntimeException) {
                throw $e;
            }

            throw new RuntimeException('ERP XPTO request failed: ' . $e->getMessage(), 0, $e);
        }
    }
}
