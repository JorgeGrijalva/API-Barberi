<?php
declare(strict_types=1);

namespace App\Models;

use App\Traits\Areas;
use App\Traits\Cities;
use App\Traits\Countries;
use App\Traits\Regions;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * App\Models\ShopLocation
 *
 * @property int $id
 * @property int $shop_id
 * @property int $region_id
 * @property int|null $country_id
 * @property int|null $city_id
 * @property int|null $area_id
 * @property int $type
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @mixin Eloquent
 */
class ShopLocation extends Model
{
    use Regions, Countries, Cities, Areas;

    protected $guarded = ['id'];

    const PRODUCT = 1;
    const SERVICE = 2;

    const TYPES = [
        self::PRODUCT => self::PRODUCT,
        self::SERVICE => self::SERVICE,
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function scopeFilter($query, array $filter) {
        $query
            ->when(data_get($filter, 'shop_id'),    fn($q, $shopId) => $q->where('shop_id',    $shopId))
            ->when(data_get($filter, 'type'),       fn($q, $type)   => $q->where('type',       $type))
            ->when(data_get($filter, 'region_id'),  fn($q, $id)     => $q->where('region_id',  $id))
            ->when(data_get($filter, 'country_id'), fn($q, $id)     => $q->where('country_id', $id))
            ->when(data_get($filter, 'city_id'),    fn($q, $id)     => $q->where('city_id',    $id))
            ->when(data_get($filter, 'area_id'),    fn($q, $id)     => $q->where('area_id',    $id));
    }

}
