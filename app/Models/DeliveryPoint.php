<?php
declare(strict_types=1);

namespace App\Models;

use App\Traits\Areas;
use App\Traits\Cities;
use App\Traits\Countries;
use App\Traits\Loadable;
use App\Traits\Regions;
use App\Traits\Reviewable;
use App\Traits\SetCurrency;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Schema;

/**
 * App\Models\DeliveryPoint
 *
 * @property int $id
 * @property int|null $active
 * @property int|null $region_id
 * @property int|null $country_id
 * @property int|null $city_id
 * @property int|null $area_id
 * @property float|null $price
 * @property array|null $address
 * @property string|null $location
 * @property int|null $fitting_rooms
 * @property string|null $img
 * @property int|null $r_count
 * @property float|null $r_avg
 * @property float|null $r_sum
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Collection|DeliveryPointWorkingDay[] $workingDays
 * @property Collection|DeliveryPointClosedDate[] $closedDates
 * @property int|null $working_days_count
 * @property int|null $closed_dates_count
 * @property-read DeliveryPointTranslation|null $translation
 * @property-read Collection|DeliveryPointTranslation[] $translations
 * @property-read int|null $translations_count
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self filter(array $filter)
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereUpdatedAt($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereImg($value)
 * @method static Builder|self whereActive($value)
 * @method static Builder|self active($value)
 * @mixin Eloquent
 */
class DeliveryPoint extends Model
{
    use Loadable, Reviewable, SetCurrency, Regions, Countries, Cities, Areas;

    protected $guarded = ['id'];

    protected $casts   = [
        'address'       => 'array',
        'location'      => 'array',
        'fitting_rooms' => 'int',
        'active'        => 'bool',
    ];

    public function translation(): HasOne
    {
        return $this->hasOne(DeliveryPointTranslation::class);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(DeliveryPointTranslation::class);
    }

    public function workingDays(): HasMany
    {
        return $this->hasMany(DeliveryPointWorkingDay::class);
    }

    public function closedDates(): HasMany
    {
        return $this->hasMany(DeliveryPointClosedDate::class);
    }

    public function scopeActive($query): Builder
    {
        /** @var DeliveryPoint $query */
        return $query->where('active', true);
    }

    public function scopeFilter($query, array $filter) {

        $column = data_get($filter, 'column','id');

        if ($column !== 'id') {
            $column = Schema::hasColumn('delivery_points', $column) ? $column : 'id';
        }

        $query
            ->when(data_get($filter, 'price_from'), function ($q) use ($filter) {

                $q
                    ->where('price', '>=', data_get($filter, 'price_from') / $this->currency())
                    ->where('price', '<=', data_get($filter, 'price_to',10000000000) / $this->currency());

            })
            ->when(data_get($filter, 'rating'), function (Builder $q, $rating) {

                $q
                    ->where('r_avg', '>=', data_get($rating, 0, 0))
                    ->where('r_avg', '<=', data_get($rating, 1, 5));

            })
            ->when(data_get($filter, 'region_id'),    fn($q,  $regionId)      => $q->where('region_id',     $regionId))
            ->when(data_get($filter, 'country_id'),   fn($q,  $countryId)     => $q->where('country_id',    $countryId))
            ->when(data_get($filter, 'city_id'),      fn($q,  $cityId)        => $q->where('city_id',       $cityId))
            ->when(data_get($filter, 'area_id'),      fn($q,  $areaId)        => $q->where('area_id',       $areaId))
            ->when(data_get($filter, 'fitting_rooms'), fn($q, $fittingRooms)  => $q->where('fitting_rooms', $fittingRooms))
            ->when(isset($filter['active']), fn($q) => $q->where('active', $filter['active']))
            ->orderBy($column, $filter['sort'] ?? 'desc');
    }
}
