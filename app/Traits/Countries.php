<?php
declare(strict_types=1);

namespace App\Traits;

use App\Models\Country;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int|null $country_id
 * @property Country|null $country
*/
trait Countries
{
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}
