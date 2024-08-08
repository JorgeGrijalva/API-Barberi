<?php
declare(strict_types=1);

namespace App\Observers;

use App\Models\Area;

class AreaObserver
{
    /**
     * Handle the Brand "updated" event.
     *
     * @param Area $model
     * @return void
     */
    public function updated(Area $model): void
    {
        RegionRelationsObserver::area($model);
    }

}
