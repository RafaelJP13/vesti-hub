<?php

namespace App\Console\Commands;

use App\Services\ProductSyncService;
use Illuminate\Console\Command;

class SyncProductsCommand extends Command
{
    protected $signature = 'products:sync';

    protected $description = 'Synchronize products from the configured ERP to Vesti';

    public function __construct(
        private readonly ProductSyncService $productSyncService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $result = $this->productSyncService->sync();

        $this->info(sprintf('Processed %d products.', count($result)));

        return self::SUCCESS;
    }
}
