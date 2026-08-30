<?php

namespace App\Services\Vesti;

use App\Contracts\SalesPlatformInterface;
use App\DTOs\ProductData;
use App\Mappers\VestiPayloadMapper;
use Illuminate\Support\Facades\Http;
use JsonException;
use RuntimeException;

class VestiClient implements SalesPlatformInterface
{
    public function __construct(
        private readonly VestiPayloadMapper $payloadMapper = new VestiPayloadMapper(),
    ) {
    }

    public function createProduct(ProductData $product): array
    {
        $apiUrl = $this->readConfig('VESTI_API_URL');
        $companyId = $this->readConfig('VESTI_COMPANY_ID');
        $apiKey = $this->readConfig('VESTI_API_KEY');

        $this->validateApiUrl($apiUrl);
        $this->validateApiKey($apiKey);
        $this->validateCompanyId($companyId);

        $payload = $this->payloadMapper->map($product);
        $url = rtrim($apiUrl, '/') . '/v1/products/company/' . $companyId;

        try {
            $response = Http::timeout(10)
                ->withHeader('apikey', $apiKey)
                ->withHeader('Content-Type', 'application/json')
                ->post($url, $payload);

            if (! $response->successful()) {
                throw new RuntimeException(sprintf('Vesti API request failed with status %d: %s', $response->status(), $response->body()));
            }

            try {
                return json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                throw new RuntimeException('Vesti API response is not valid JSON.', 0, $e);
            }
        } catch (\Throwable $e) {
            if ($e instanceof RuntimeException) {
                throw $e;
            }

            throw new RuntimeException('Vesti API request failed: ' . $e->getMessage(), 0, $e);
        }
    }

    private function readConfig(string $key): string
    {
        $value = getenv($key);

        if ($value !== false && $value !== null) {
            return (string) $value;
        }

        $value = $_ENV[$key] ?? $_SERVER[$key] ?? null;

        if (is_string($value)) {
            return $value;
        }

        return (string) env($key, '');
    }

    private function validateApiUrl(string $apiUrl): void
    {
        if ($apiUrl === '' || filter_var($apiUrl, FILTER_VALIDATE_URL) === false) {
            throw new RuntimeException('VESTI_API_URL must be a valid absolute URL.');
        }
    }

    private function validateApiKey(string $apiKey): void
    {
        if ($apiKey === '') {
            throw new RuntimeException('VESTI_API_KEY must be configured.');
        }
    }

    private function validateCompanyId(string $companyId): void
    {
        if (! preg_match('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/', $companyId)) {
            throw new RuntimeException('VESTI_COMPANY_ID must be a valid UUID.');
        }
    }
}
