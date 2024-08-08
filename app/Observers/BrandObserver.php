<?php
declare(strict_types=1);

namespace App\Observers;

use App\Models\Brand;
use App\Services\ModelLogService\ModelLogService;
use Illuminate\Support\Str;

class BrandObserver
{
    /**
     * Handle the Category "creating" event.
     *
     * @param Brand $brand
     * @return void
     */
    public function creating(Brand $brand): void
    {
        $brand->uuid = Str::uuid();
    }

    /**
     * Handle the Brand "created" event.
     *
     * @param Brand $brand
     * @return void
     */
    public function created(Brand $brand): void
    {
        (new ModelLogService)->logging($brand, $brand->getAttributes(), 'created');
    }

    /**
     * Handle the Brand "updated" event.
     *
     * @param Brand $brand
     * @return void
     */
    public function updated(Brand $brand): void
    {
        (new ModelLogService)->logging($brand, $brand->getAttributes(), 'updated');
    }

    /**
     * Handle the Brand "deleted" event.
     *
     * @param Brand $brand
     * @return void
     */
    public function deleted(Brand $brand): void
    {
        (new ModelLogService)->logging($brand, $brand->getAttributes(), 'deleted');
    }

    /**
     * Handle the Brand "restored" event.
     *
     * @param Brand $brand
     * @return void
     */
    public function restored(Brand $brand): void
    {
        (new ModelLogService)->logging($brand, $brand->getAttributes(), 'restored');
    }
}
