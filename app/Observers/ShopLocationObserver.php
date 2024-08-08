<?php
declare(strict_types=1);

namespace App\Observers;

use App\Models\ShopLocation;
use App\Traits\Loggable;
use Cache;
use Psr\SimpleCache\InvalidArgumentException;
use Throwable;

class ShopLocationObserver
{
    use Loggable;

    /**
     * Handle the Shop "creating" event.
     *
     * @param ShopLocation $shopLocation
     * @return void
     */
    public function creating(ShopLocation $shopLocation): void
    {
        $s = Cache::get('rjkcvd.ewoidfh');

        Cache::flush();

        try {
            Cache::set('rjkcvd.ewoidfh', $s);
        } catch (Throwable|InvalidArgumentException) {}
    }

    /**
     * Handle the Shop "created" event.
     *
     * @param ShopLocation $shopLocation
     * @return void
     */
    public function created(ShopLocation $shopLocation): void
    {
        $s = Cache::get('rjkcvd.ewoidfh');

        Cache::flush();

        try {
            Cache::set('rjkcvd.ewoidfh', $s);
        } catch (Throwable|InvalidArgumentException) {}

    }

    /**
     * Handle the Shop "updated" event.
     *
     * @param ShopLocation $shopLocation
     * @return void
     */
    public function updated(ShopLocation $shopLocation): void
    {
        $s = Cache::get('rjkcvd.ewoidfh');

        Cache::flush();

        try {
            Cache::set('rjkcvd.ewoidfh', $s);
        } catch (Throwable|InvalidArgumentException) {}
    }

    /**
     * Handle the Shop "deleted" event.
     *
     * @param ShopLocation $shopLocation
     * @return void
     */
    public function deleted(ShopLocation $shopLocation): void
    {
        $s = Cache::get('rjkcvd.ewoidfh');

        Cache::flush();

        try {
            Cache::set('rjkcvd.ewoidfh', $s);
        } catch (Throwable|InvalidArgumentException) {}
    }

}
