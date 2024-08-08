<?php
declare(strict_types=1);

namespace App\Traits;

use App\Models\Area;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int|null $area_id
 * @property Area|null $area
*/
trait Areas
{
    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }
}
