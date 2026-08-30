<?php

namespace App\Providers;

use App\Console\Commands\SyncProductsCommand;
use App\Contracts\ErpClientInterface;
use App\Contracts\ProductMapperInterface;
use App\Contracts\SalesPlatformInterface;
use App\Services\Erp\ErpProviderResolver;
use App\Services\Vesti\VestiClient;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ErpClientInterface::class, function (): ErpClientInterface {
            return (new ErpProviderResolver())->resolveClient();
        });

        $this->app->bind(ProductMapperInterface::class, function (): ProductMapperInterface {
            return (new ErpProviderResolver())->resolveMapper();
        });

        $this->app->bind(SalesPlatformInterface::class, VestiClient::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->commands([
            SyncProductsCommand::class,
        ]);
    }
}
