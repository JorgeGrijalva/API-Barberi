<?php
declare(strict_types=1);

namespace App\Observers;

use App\Models\Country;

class CountryObserver
{
    /**
     * Handle the Brand "updated" event.
     *
     * @param Country $model
     * @return void
     */
    public function updated(Country $model): void
    {
        $model->cities()->update(['region_id' => $model->region_id]);
        $model->areas()->update(['region_id'  => $model->region_id]);

        RegionRelationsObserver::country($model);
    }

}
