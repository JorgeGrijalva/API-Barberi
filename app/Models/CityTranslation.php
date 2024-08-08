<?php
declare(strict_types=1);

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\CityTranslation
 *
 * @property int $id
 * @property int $city_id
 * @property string $locale
 * @property string $title
 * @property City|null $city
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereCityId($value)
 * @method static Builder|self whereTitle($value)
 * @mixin Eloquent
 */
class CityTranslation extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
