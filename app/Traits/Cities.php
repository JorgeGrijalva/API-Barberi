<?php
declare(strict_types=1);

namespace App\Traits;

use App\Models\City;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int|null $city_id
 * @property City|null $city
*/
trait Cities
{
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
