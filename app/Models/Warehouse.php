<?php
declare(strict_types=1);

namespace App\Models;

use App\Traits\Areas;
use App\Traits\Cities;
use App\Traits\Countries;
use App\Traits\Loadable;
use App\Traits\Regions;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Schema;

/**
 * App\Models\Warehouse
 *
 * @property int $id
 * @property int|null $active
 * @property int|null $region_id
 * @property int|null $country_id
 * @property int|null $city_id
 * @property int|null $area_id
 * @property array|null $address
 * @property string|null $location
 * @property string|null $img
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Collection|WarehouseWorkingDay[] $workingDays
 * @property Collection|WarehouseClosedDate[] $closedDates
 * @property int|null $working_days_count
 * @property int|null $closed_dates_count
 * @property-read WarehouseTranslation|null $translation
 * @property-read Collection|WarehouseTranslation[] $translations
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

class Warehouse extends Model
{
    use Loadable, Regions, Countries, Cities, Areas;

    protected $guarded = ['id'];
    protected $casts   = [
        'address'       => 'array',
        'location'      => 'array',
        'active'        => 'bool',
    ];

    public function translation(): HasOne
    {
        return $this->hasOne(WarehouseTranslation::class);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(WarehouseTranslation::class);
    }

    public function workingDays(): HasMany
    {
        return $this->hasMany(WarehouseWorkingDay::class);
    }

    public function closedDates(): HasMany
    {
        return $this->hasMany(WarehouseClosedDate::class);
    }

    public function scopeActive($query): Builder
    {
        /** @var Warehouse $query */
        return $query->where('active', true);
    }

    public function scopeFilter($query, array $filter) {

        $column = $filter['column'] ?? 'id';

        if ($column !== 'id') {
            $column = Schema::hasColumn('warehouses', $column) ? $column : 'id';
        }

        $query
            ->when(data_get($filter, 'region_id'),  fn($q,  $regionId)  => $q->where('region_id',  $regionId))
            ->when(data_get($filter, 'country_id'), fn($q,  $countryId) => $q->where('country_id', $countryId))
            ->when(data_get($filter, 'city_id'),    fn($q,  $cityId)    => $q->where('city_id',    $cityId))
            ->when(data_get($filter, 'area_id'),    fn($q,  $areaId)    => $q->where('area_id',    $areaId))
            ->when(isset($filter['active']), fn($q) => $q->where('active', $filter['active']))
            ->orderBy($column, $filter['column'] ?? 'desc');
    }
}
