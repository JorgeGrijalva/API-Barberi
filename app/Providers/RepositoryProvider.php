<?php
declare(strict_types=1);

namespace App\Providers;

use App\Services\BrandService\BrandService;
use App\Services\Interfaces\BrandServiceInterface;
use Illuminate\Support\ServiceProvider;

class RepositoryProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->bind(BrandServiceInterface::class, BrandService::class);
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {

    }
}
