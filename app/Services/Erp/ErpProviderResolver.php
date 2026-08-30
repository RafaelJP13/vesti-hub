<?php

namespace App\Services\Erp;

use App\Contracts\ErpClientInterface;
use App\Contracts\ProductMapperInterface;
use App\Mappers\XptoProductMapper;
use App\Mappers\XyzProductMapper;
use RuntimeException;

class ErpProviderResolver
{
    public function __construct(
        private readonly ?string $provider = null,
    ) {
    }

    public function resolveClient(): ErpClientInterface
    {
        return match ($this->provider() ?? env('ERP_PROVIDER', 'xpto')) {
            'xpto' => new XptoErpClient(),
            'xyz' => new XyzErpClient(),
            default => throw new RuntimeException(sprintf('Unsupported ERP provider "%s". Supported providers: xpto, xyz.', $this->provider() ?? env('ERP_PROVIDER', 'xpto'))),
        };
    }

    public function resolveMapper(): ProductMapperInterface
    {
        return match ($this->provider() ?? env('ERP_PROVIDER', 'xpto')) {
            'xpto' => new XptoProductMapper(),
            'xyz' => new XyzProductMapper(),
            default => throw new RuntimeException(sprintf('Unsupported ERP provider "%s". Supported providers: xpto, xyz.', $this->provider() ?? env('ERP_PROVIDER', 'xpto'))),
        };
    }

    private function provider(): ?string
    {
        if ($this->provider !== null) {
            return strtolower(trim($this->provider));
        }

        $value = getenv('ERP_PROVIDER');

        return $value !== false && $value !== null ? strtolower(trim((string) $value)) : null;
    }
}
