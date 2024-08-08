<?php
declare(strict_types=1);

namespace App\Traits;

use App\Models\Region;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int|null $region_id
 * @property Region|null $region
*/
trait Regions
{
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }
}
