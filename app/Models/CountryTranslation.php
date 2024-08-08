<?php
declare(strict_types=1);

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * App\Models\CountryTranslation
 *
 * @property int $id
 * @property int $country_id
 * @property string $locale
 * @property string $title
 * @property Country|null $country
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereCountryId($value)
 * @method static Builder|self whereTitle($value)
 * @mixin Eloquent
 */
class CountryTranslation extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}
