<?php
declare(strict_types=1);

namespace App\Models;

use App\Traits\Loadable;
use App\Traits\SetCurrency;
use Carbon\Carbon;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * App\Models\ServiceMaster
 * @property int $id
 * @property string $type
 * @property int $service_id
 * @property int $master_id
 * @property int $shop_id
 * @property double $commission_fee
 * @property double $price
 * @property double $discount
 * @property boolean $active
 * @property int $interval
 * @property int $pause
 * @property array $data
 * @property int $gender
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read double $rate_price
 * @property-read double $rate_discount
 * @property-read double $total_price
 * @property-read double $rate_total_price
 * @property-read double $rate_commission_fee
 * @property-read Service $service
 * @property-read User $master
 * @property-read Shop $shop
 * @property-read Collection|ServiceExtra[] $extras
 * @property-read Collection|ServiceMasterPrice[] $pricing
 * @property-read Collection|ServiceMasterNotification[] $notifications
 * @method static Builder|self actual()
 * @method static Builder|self filter(array $filter)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereId($value)
 * @mixin Eloquent
 */
class ServiceMaster extends Model
{
    use Loadable;
    use SetCurrency;

    public $guarded = ['id'];

    protected $casts = [
        'active' => 'bool',
        'data'   => 'array',
    ];

    public function getRatePriceAttribute(): float|int|null
    {
        if (request()->is('api/v1/dashboard/user/*') || request()->is('api/v1/rest/*')) {
            return $this->price * $this->currency();
        }

        return $this->price;
    }

    public function getRateDiscountAttribute(): float|int|null
    {
        if (request()->is('api/v1/dashboard/user/*') || request()->is('api/v1/rest/*')) {
            return $this->discount * $this->currency();
        }

        return $this->discount;
    }

    public function getTotalPriceAttribute(): float|int|null
    {
        return $this->price - $this->discount;
    }

    public function getRateTotalPriceAttribute(): float|int|null
    {
        if (request()->is('api/v1/dashboard/user/*') || request()->is('api/v1/rest/*')) {
            return $this->total_price * $this->currency();
        }

        return $this->total_price;
    }

    public function getRateCommissionFeeAttribute(): float|int|null
    {
        if (request()->is('api/v1/dashboard/user/*') || request()->is('api/v1/rest/*')) {
            return $this->commission_fee * $this->currency();
        }

        return $this->commission_fee;
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function pricing(): HasMany
    {
        return $this->hasMany(ServiceMasterPrice::class);
    }

    public function master(): BelongsTo
    {
        return $this->belongsTo(User::class, 'master_id');
    }

    public function extras(): HasMany
    {
        return $this->hasMany(ServiceExtra::class, 'service_id', 'service_id');
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class, 'master_id', 'user_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(ServiceMasterNotification::class);
    }

    public function scopeActual($query)
    {
        return $query->where('active', true);
    }

    public function scopeFilter($query, array $filter) {
        $query
            ->when(data_get($filter, 'type'),      fn($q, $type) => $q->where('type',      $type))
            ->when(data_get($filter, 'shop_id'),   fn($q, $id)   => $q->where('shop_id',   $id))
            ->when(data_get($filter, 'master_id'), fn($q, $id)   => $q->where('master_id', $id))
            ->when(data_get($filter, 'invite_status'), function ($q, $status) use ($filter) {
                $q->whereHas('invitations', function ($q) use ($status, $filter) {
                    $q
                        ->when(data_get($filter, 'shop_id'), fn($q, $id) => $q->where('shop_id', $id))
                        ->where('status', $status);
                });
            })
            ->when(data_get($filter, 'service_id'),    fn($q, $id)     => $q->where('service_id', $id))
            ->when(data_get($filter, 'price_from'),    fn($q, $price)  => $q->where('price', '>=', $price))
            ->when(data_get($filter, 'price_to'),      fn($q, $price)  => $q->where('price', '<=', $price))
            ->when(data_get($filter, 'pause_from'),    fn($q, $price)  => $q->where('pause', '>=', $price))
            ->when(data_get($filter, 'pause_to'),      fn($q, $price)  => $q->where('pause', '<=', $price))
            ->when(data_get($filter, 'interval_from'), fn($q, $price)  => $q->where('interval', '>=', $price))
            ->when(data_get($filter, 'interval_to'),   fn($q, $price)  => $q->where('interval', '<=', $price))
            ->when(data_get($filter, 'gender'),        fn($q, $gender) => $q->where('gender', $gender))
            ->when(isset($filter['active']),                fn($q)         => $q->where('active', $filter['active']))
            ->when(isset($filter['has_discount']),          fn($q)         => $q->where('discount', '>', 0));
    }
}
