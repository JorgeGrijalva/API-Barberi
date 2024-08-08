<?php
declare(strict_types=1);

namespace App\Models;

use App\Traits\Areas;
use App\Traits\Cities;
use App\Traits\Countries;
use App\Traits\Regions;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Carbon;

/**
 * App\Models\Cart
 *
 * @property int $id
 * @property int $owner_id
 * @property double $total_price
 * @property double $rate_total_price
 * @property int $status
 * @property int $currency_id
 * @property int $region_id
 * @property int $country_id
 * @property int $city_id
 * @property int $area_id
 * @property int $rate
 * @property boolean $group
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read UserCart $userCart
 * @property-read User|BelongsTo $user
 * @property-read Currency|BelongsTo $currency
 * @property-read Order|BelongsTo $order
 * @property-read UserCart[]|HasMany $userCarts
 * @property-read int|null $user_carts_count
 * @property-read PaymentProcess|null $paymentProcess
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self filter(array $filter)
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereOwnerId($value)
 * @method static Builder|self whereStatus($value)
 * @method static Builder|self whereStockId($value)
 * @method static Builder|self whereTotalPrice($value)
 * @method static Builder|self whereUpdatedAt($value)
 * @mixin Eloquent
 */
class Cart extends Model
{
    use HasFactory, Regions, Countries, Cities, Areas;

    protected $guarded = ['id'];


    protected $casts = [
        'status'        => 'bool',
        'group'         => 'bool',
        'rate'          => 'float',
        'total_price'   => 'float',
        'created_at'    => 'datetime:Y-m-d H:i:s',
        'updated_at'    => 'datetime:Y-m-d H:i:s',
    ];

    public function getRateTotalPriceAttribute(): float|int|null
    {
        if (request()->is('api/v1/dashboard/user/*') || request()->is('api/v1/rest/*')) {
            return $this->total_price * ($this->rate <= 0 ? 1 : $this->rate);
        }

        return $this->total_price;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class,'owner_id','id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function userCarts(): HasMany
    {
        return $this->hasMany(UserCart::class);
    }

    public function userCart(): HasOne
    {
        return $this->hasOne(UserCart::class);
    }

    public function paymentProcess(): MorphOne
    {
        return $this->morphOne(PaymentProcess::class, 'model');
    }

    public function scopeFilter($query, array $filter) {

        $regionId   = data_get($filter, 'region_id');
        $countryId  = data_get($filter, 'country_id');
        $cityId     = data_get($filter, 'city_id');
        $areaId     = data_get($filter, 'area_id');

        $byLocation = $regionId || $countryId || $cityId || $areaId;

        $query
            ->when(data_get($filter, 'user_cart_uuid'), fn($q, $uuid) => $q->whereHas('userCarts', fn($q) => $q->where('uuid', $uuid)))
            ->when(data_get($filter, 'cart_id'), fn($q, $cartId) => $q->where('id', $cartId))
            ->when(data_get($filter, 'user_id'), fn($q, $userId) => $q->where('owner_id', $userId))
            ->when($byLocation, function ($query) use ($regionId, $countryId, $cityId, $areaId) {
                $query->where(function ($query) use ($regionId, $countryId, $cityId, $areaId) {
                    $query->where('region_id', $regionId)
                        ->where('country_id', $countryId)
                        ->where('city_id', $cityId)
                        ->where('area_id', $areaId);
                });
            });
    }
}
