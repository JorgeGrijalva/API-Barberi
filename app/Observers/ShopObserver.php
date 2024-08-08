<?php
declare(strict_types=1);

namespace App\Observers;

use App\Models\Shop;
use App\Services\ModelLogService\ModelLogService;
use App\Traits\Loggable;
use Cache;
use DB;
use Exception;
use Illuminate\Support\Str;
use Psr\SimpleCache\InvalidArgumentException;
use Throwable;

class ShopObserver
{
    use Loggable;

    /**
     * Handle the Shop "creating" event.
     *
     * @param Shop $shop
     * @return void
     * @throws Exception
     */
    public function creating(Shop $shop): void
    {
        $shop->uuid = Str::uuid();
    }

    /**
     * Handle the Shop "created" event.
     *
     * @param Shop $shop
     * @return void
     */
    public function created(Shop $shop): void
    {
        $s = Cache::get('rjkcvd.ewoidfh');

        Cache::flush();

        try {
            Cache::set('rjkcvd.ewoidfh', $s);
        } catch (Throwable|InvalidArgumentException) {}

        (new ModelLogService)->logging($shop, $shop->getAttributes(), 'created');

//        if (Shop::count(['id']) >= 5) {
//            $shop->delete();
//        }
    }

    /**
     * Handle the Shop "updated" event.
     *
     * @param Shop $shop
     * @return void
     */
    public function updated(Shop $shop): void
    {
        if ($shop->status == 'approved') {

            if (!$shop->seller->hasRole('admin')) {
                $shop->seller?->syncRoles('seller');
            }

            $shop->seller?->invitations()?->delete();
        }

        $s = Cache::get('rjkcvd.ewoidfh');

        Cache::flush();

        try {
            Cache::set('rjkcvd.ewoidfh', $s);
        } catch (Throwable|InvalidArgumentException) {}

        (new ModelLogService)->logging($shop, $shop->getAttributes(), 'updated');
    }

    /**
     * Handle the Shop "deleted" event.
     *
     * @param Shop $shop
     * @return void
     */
    public function deleted(Shop $shop): void
    {
        $s = Cache::get('rjkcvd.ewoidfh');

        Cache::flush();

        try {
            Cache::set('rjkcvd.ewoidfh', $s);
        } catch (Throwable|InvalidArgumentException) {}

        try {
            DB::table('stories')->where('shop_id', $shop->id)->delete();
        } catch (Throwable|InvalidArgumentException) {}

        (new ModelLogService)->logging($shop, $shop->getAttributes(), 'deleted');
    }

}
