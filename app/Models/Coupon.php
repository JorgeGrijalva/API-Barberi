<?php
declare(strict_types=1);

namespace App\Models;

use App\Traits\Loadable;
use Database\Factories\CouponFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Schema;

/**
 * App\Models\Coupon
 *
 * @property int $id
 * @property string $name
 * @property string $type
 * @property string $for
 * @property int $qty
 * @property int $shop_id
 * @property float $price
 * @property string $expired_at
 * @property string|null $img
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection|Gallery[] $galleries
 * @property-read int|null $galleries_count
 * @property-read Collection|OrderCoupon[] $orderCoupons
 * @property-read int|null $order_coupons_count
 * @property-read Collection|BookingCoupon[] $bookingCoupons
 * @property-read int|null $booking_coupons_count
 * @property-read Shop $shop
 * @property-read CouponTranslation|null $translation
 * @property-read Collection|CouponTranslation[] $translations
 * @property-read int|null $translations_count
 * @method static Builder|self checkCoupon(string $coupon, int $shopId)
 * @method static CouponFactory factory(...$parameters)
 * @method static Builder|self filter($filter)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self increment($column, $amount = 1, array $extra = [])
 * @method static Builder|self decrement($column, $amount = 1, array $extra = [])
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereExpiredAt($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereImg($value)
 * @method static Builder|self whereName($value)
 * @method static Builder|self wherePrice($value)
 * @method static Builder|self whereQty($value)
 * @method static Builder|self whereType($value)
 * @method static Builder|self whereUpdatedAt($value)
 * @mixin Eloquent
 */
class Coupon extends Model
{
    use HasFactory, Loadable;

    protected $guarded = ['id'];

    const totalPrice        = 'total_price';
    const deliveryFee       = 'delivery_fee';
    const bookingTotalPrice = 'booking_total_price';

    const FOR = [
        self::totalPrice        => self::totalPrice,
        self::deliveryFee       => self::deliveryFee,
        self::bookingTotalPrice => self::bookingTotalPrice,
    ];

    public function translations(): HasMany
    {
        return $this->hasMany(CouponTranslation::class);
    }

    public function translation(): HasOne
    {
        return $this->hasOne(CouponTranslation::class);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function orderCoupons(): HasMany
    {
        return $this->hasMany(OrderCoupon::class, 'name', 'name');
    }

    public function bookingCoupons(): HasMany
    {
        return $this->hasMany(BookingCoupon::class, 'name', 'name');
    }

    public static function scopeCheckCoupon($query, string $coupon, int $shopId) {
        return $query
            ->where('name', $coupon)
            ->where('shop_id', $shopId)
            ->where('qty', '>', 0)
            ->whereDate('expired_at', '>', now());
    }

    public function scopeFilter($query, array $filter)
    {
        $column = data_get($filter, 'column','id');

        if ($column !== 'id') {
            $column = Schema::hasColumn('coupons', $column) ? $column : 'id';
        }

        $query
            ->when(data_get($filter, 'type'), function ($q, $type) {
                $q->where('type', $type);
            })
            ->when(data_get($filter, 'for'), function ($q, $for) {
                $q->where('for', $for);
            })
            ->when(data_get($filter, 'price'), function ($q, $price) {
                $q->where('price', $price);
            })
            ->when(data_get($filter, 'qty'), function ($q, $qty) {
                $q->where('qty', $qty);
            })
            ->when(data_get($filter, 'shop_id'), function ($q, $shopId) {
                $q->where('shop_id', $shopId);
            })
            ->when(data_get($filter, 'expired_from'), function ($q, $expiredFrom) use ($filter) {
                $expiredFrom = date('Y-m-d', strtotime($expiredFrom));

                $expiredTo = data_get($filter, 'expired_to', date('Y-m-d'));

                $expiredTo = date('Y-m-d', strtotime($expiredTo));

                $q->where([
                    ['expired_at', '>=', $expiredFrom],
                    ['expired_at', '<=', $expiredTo],
                ]);
            })
            ->orderBy($column, $filter['sort'] ?? 'desc');
    }
}
