<?php
declare(strict_types=1);

namespace App\Observers;

use App\Models\City;

class CityObserver
{
    /**
     * Handle the Brand "updated" event.
     *
     * @param City $model
     * @return void
     */
    public function updated(City $model): void
    {
        $model->areas()->update([
            'region_id'  => $model->region_id,
            'country_id' => $model->country_id,
        ]);

        RegionRelationsObserver::city($model);
    }

}
