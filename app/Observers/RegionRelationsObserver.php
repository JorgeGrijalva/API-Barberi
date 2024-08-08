<?php
declare(strict_types=1);

namespace App\Observers;

use App\Models\Area;
use App\Models\Cart;
use App\Models\City;
use App\Models\Country;
use App\Models\DeliveryPoint;
use App\Models\DeliveryPrice;
use App\Models\ShopLocation;
use App\Models\UserAddress;

class RegionRelationsObserver
{
    /**
     * Handle the Brand "updated" event.
     *
     * @param Area $model
     * @return void
     */
    public static function area(Area $model): void
    {
        DeliveryPrice::where('area_id', $model->id)->update([
            'region_id'  => $model->region_id,
            'country_id' => $model->country_id,
            'city_id'    => $model->city_id,
        ]);

        DeliveryPoint::where('area_id', $model->id)->update([
            'region_id'  => $model->region_id,
            'country_id' => $model->country_id,
            'city_id'    => $model->city_id,
        ]);

        UserAddress::where('area_id', $model->id)->update([
            'region_id'  => $model->region_id,
            'country_id' => $model->country_id,
            'city_id'    => $model->city_id,
        ]);

        Cart::where('area_id', $model->id)->update([
            'region_id'  => $model->region_id,
            'country_id' => $model->country_id,
            'city_id'    => $model->city_id,
        ]);

        ShopLocation::where('area_id', $model->id)->update([
            'region_id'  => $model->region_id,
            'country_id' => $model->country_id,
            'city_id'    => $model->city_id,
        ]);
    }

    public static function city(City $model): void
    {
        DeliveryPrice::where('city_id', $model->id)->update([
            'region_id'  => $model->region_id,
            'country_id' => $model->country_id,
        ]);

        DeliveryPoint::where('city_id', $model->id)->update([
            'region_id'  => $model->region_id,
            'country_id' => $model->country_id,
        ]);

        UserAddress::where('city_id', $model->id)->update([
            'region_id'  => $model->region_id,
            'country_id' => $model->country_id,
        ]);

        Cart::where('city_id', $model->id)->update([
            'region_id'  => $model->region_id,
            'country_id' => $model->country_id,
        ]);

        ShopLocation::where('city_id', $model->id)->update([
            'region_id'  => $model->region_id,
            'country_id' => $model->country_id,
        ]);
    }

    public static function country(Country $model): void
    {
        DeliveryPrice::where('country_id', $model->id)->update([
            'region_id' => $model->region_id,
        ]);

        DeliveryPoint::where('country_id', $model->id)->update([
            'region_id' => $model->region_id,
        ]);

        UserAddress::where('country_id', $model->id)->update([
            'region_id' => $model->region_id,
        ]);

        Cart::where('country_id', $model->id)->update([
            'region_id' => $model->region_id,
        ]);

        ShopLocation::where('country_id', $model->id)->update([
            'region_id' => $model->region_id,
        ]);
    }
}
