<?php
declare(strict_types=1);

namespace App\Models;

use App\Traits\Areas;
use App\Traits\ByLocation;
use App\Traits\Cities;
use App\Traits\Countries;
use App\Traits\Regions;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Schema;

/**
 * App\Models\DeliveryPrice
 *
 * @property int $id
 * @property float|null $price
 * @property integer $region_id
 * @property integer $country_id
 * @property integer $city_id
 * @property integer $area_id
 * @property integer $shop_id
 * @method static Builder|self active()
 * @method static Builder|self filter(array $filter)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereId($value)
 * @mixin Eloquent
 */
class DeliveryPrice extends Model
{
    use Regions, Countries, Cities, Areas, ByLocation;

    public $guarded     = ['id'];
    public $timestamps  = false;

    public function translation(): HasOne
    {
        return $this->hasOne(DeliveryPriceTranslation::class);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(DeliveryPriceTranslation::class);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function scopeFilter($query, array $filter) {

        $column = $filter['column'] ?? 'id';

        if ($column !== 'id') {
            $column = Schema::hasColumn('delivery_prices', $column) ? $column : 'id';
        }

        $query
            ->when(data_get($filter, 'region_id'),  fn($q, $regionId)    => $q->where('region_id',  $regionId))
            ->when(data_get($filter, 'country_id'), fn($q, $countryId)   => $q->where('country_id', $countryId), !empty(data_get($filter, 'region_id')) ? fn($q) => $q->whereNull('country_id') : fn($q) => $q)
            ->when(data_get($filter, 'city_id'),    fn($q, $cityId)      => $q->where('city_id',    $cityId),    !empty(data_get($filter, 'country_id')) ? fn($q) => $q->whereNull('city_id') : fn($q) => $q)
            ->when(data_get($filter, 'area_id'),    fn($q, $areaId)      => $q->where('area_id',    $areaId),    !empty(data_get($filter, 'area_id'))    ? fn($q) => $q->whereNull('area_id') : fn($q) => $q)
            ->when(data_get($filter, 'shop_id'),    fn($q, $shopId)      => $q->where('shop_id',    $shopId))
            ->when(data_get($filter, 'search'),     fn ($query, $search) => $this->search($query,   $search))
            ->when(data_get($filter, 'search'),     function ($query, $search) {
                $query->whereHas('translations', function ($q) use ($search) {
                    $q->where('title', 'LIKE', "%$search%")->select('id', 'delivery_price_id', 'locale', 'title');
                });
            })
            ->orderBy($column, data_get($filter, 'sort', 'desc'));
    }
}
