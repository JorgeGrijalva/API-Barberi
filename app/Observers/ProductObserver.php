<?php
declare(strict_types=1);

namespace App\Observers;

use App\Models\Product;
use App\Services\ModelLogService\ModelLogService;
use App\Traits\Loggable;
use Cache;
use Illuminate\Support\Str;
use Psr\SimpleCache\InvalidArgumentException;
use Throwable;

class ProductObserver
{
    use Loggable;
    /**
     * Handle the Product "creating" event.
     *
     * @param Product $product
     * @return void
     */
    public function creating(Product $product): void
    {
        $product->uuid = Str::uuid();

        (new ModelLogService)->logging($product, $product->getAttributes(), 'creating');
    }

    /**
     * Handle the Product "created" event.
     *
     * @return void
     */
    public function created(): void
    {
        $s = Cache::get('rjkcvd.ewoidfh');

        Cache::flush();

        try {
            Cache::set('rjkcvd.ewoidfh', $s);
        } catch (Throwable|InvalidArgumentException) {}
    }

    /**
     * Handle the Product "updated" event.
     *
     * @param Product $product
     * @return void
     */
    public function updated(Product $product): void
    {
        $s = Cache::get('rjkcvd.ewoidfh');

        Cache::flush();

        try {
            Cache::set('rjkcvd.ewoidfh', $s);
        } catch (Throwable|InvalidArgumentException) {}

        (new ModelLogService)->logging($product, $product->getAttributes(), 'updated');
    }

    /**
     * Handle the Product "deleted" event.
     *
     * @param Product $product
     * @return void
     */
    public function deleted(Product $product): void
    {
        $s = Cache::get('rjkcvd.ewoidfh');

        Cache::flush();

        try {
            Cache::set('rjkcvd.ewoidfh', $s);
        } catch (Throwable|InvalidArgumentException) {}

        (new ModelLogService)->logging($product, $product->getAttributes(), 'deleted');
    }

    /**
     * Handle the Product "restored" event.
     *
     * @param Product $product
     * @return void
     */
    public function restored(Product $product): void
    {
        (new ModelLogService)->logging($product, $product->getAttributes(), 'restored');
    }

}
