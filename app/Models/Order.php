<?php
declare(strict_types=1);

namespace App\Models;

use App\Traits\Loadable;
use App\Traits\Payable;
use App\Traits\Reviewable;
use App\Traits\UserSearch;
use Database\Factories\OrderFactory;
use DB;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Carbon;
use Schema;

/**
 * App\Models\Order
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $deliveryman_id
 * @property int|null $address_id
 * @property int|null $delivery_price_id
 * @property int|null $delivery_point_id
 * @property int|null $currency_id
 * @property int $shop_id
 * @property string $type
 * @property double $commission_fee
 * @property string $canceled_note
 * @property-read string|null $track_name
 * @property-read string|null $track_id
 * @property-read string|null $track_url
 * @property-read int $rate_commission_fee
 * @property-read int $cart_id
 * @property-read int $parent_id
 * @property-read int $seller_fee
 * @property-read int $origin_price
 * @property string $status
 * @property double $total_price
 * @property double $delivery_fee
 * @property double $total_discount
 * @property double $total_tax
 * @property double $service_fee
 * @property float $rate
 * @property string $note
 * @property array $location
 * @property array $address
 * @property string $phone
 * @property string $username
 * @property Carbon|null $delivery_date
 * @property float $delivery_type
 * @property float|null $coupon_price
 * @property float|null $point_histories_sum_price
 * @property float|null $rate_coupon_price
 * @property boolean|null $current
 * @property string|null $img
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read int $rate_delivery_fee
 * @property-read int $rate_total_price
 * @property-read int $rate_total_tax
 * @property-read int $rate_service_fee
 * @property-read double $rate_total_discount
 * @property-read double $order_details_sum_total_price
 * @property-read double $order_details_sum_discount
 * @property-read OrderCoupon|null $coupon
 * @property-read Currency|null $currency
 * @property-read UserAddress|null $myAddress
 * @property-read Collection|OrderDetail[] $orderDetails
 * @property-read int|null $order_details_count
 * @property-read Collection|OrderStatusNote[] $notes
 * @property-read int|null $notes_count
 * @property-read Collection|OrderDetail[] $orderRefunds
 * @property-read int|null $order_refunds_count
 * @property-read int|null $order_details_sum_quantity
 * @property-read PointHistory|null $pointHistory
 * @property-read PointHistory|null $pointHistories
 * @property-read Transaction|null $transaction
 * @property-read Collection|Transaction[] $transactions
 * @property-read Collection|Order[] $children
 * @property-read int $transactions_count
 * @property-read PaymentToPartner|null $paymentToPartner
 * @property-read DeliveryPrice|null $deliveryPrice
 * @property-read DeliveryPoint|null $deliveryPoint
 * @property-read int $payment_process_count
 * @property-read User|null $user
 * @property-read User|null $deliveryman
 * @property-read Shop|null $shop
 * @property-read Cart|null $cart
 * @property-read Collection|Gallery[] $galleries
 * @property-read int|null $galleries_count
 * @property-read Collection|ModelLog[] $logs
 * @property-read int|null $logs_count
 * @method static OrderFactory factory(...$parameters)
 * @method static Builder|self filter($filter)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereUpdatedAt($value)
 * @method static Builder|self whereUserId($value)
 * @mixin Eloquent
 */
class Order extends Model
{
    use HasFactory, Payable, Reviewable, Loadable, UserSearch;

    protected $guarded = ['id'];

    protected $casts = [
        'location'       => 'array',
        'address'        => 'array',
        'type'           => 'int',
    ];

    const IN_HOUSE  = 1;
    const SELLER    = 2;

    const STATUS_NEW        = 'new';
    const STATUS_ACCEPTED   = 'accepted';
    const STATUS_READY      = 'ready';
    const STATUS_ON_A_WAY   = 'on_a_way';
    const STATUS_PAUSE      = 'pause';
    const STATUS_DELIVERED  = 'delivered';
    const STATUS_CANCELED   = 'canceled';

    const STATUSES = [
        self::STATUS_NEW        => self::STATUS_NEW,
        self::STATUS_ACCEPTED   => self::STATUS_ACCEPTED,
        self::STATUS_READY      => self::STATUS_READY,
        self::STATUS_ON_A_WAY   => self::STATUS_ON_A_WAY,
        self::STATUS_PAUSE      => self::STATUS_PAUSE,
        self::STATUS_DELIVERED  => self::STATUS_DELIVERED,
        self::STATUS_CANCELED   => self::STATUS_CANCELED,
    ];

    const TYPES = [
        self::IN_HOUSE  => self::IN_HOUSE,
        self::SELLER    => self::SELLER,
    ];

    const DELIVERY  = 'delivery';
    const POINT     = 'point';
    const DIGITAL   = 'digital';

    const DELIVERY_TYPES = [
        self::DELIVERY => self::DELIVERY,
        self::POINT    => self::POINT,
        self::DIGITAL  => self::DIGITAL,
    ];

    public function getRateTotalTaxAttribute(): ?float
    {
        if (request()->is('api/v1/dashboard/user/*') || request()->is('api/v1/rest/*')) {
            return $this->total_tax * ($this->rate <= 0 ? 1 : $this->rate);
        }

        return $this->total_tax;
    }

    public function getRateCommissionFeeAttribute(): ?float
    {
        if (request()->is('api/v1/dashboard/user/*') || request()->is('api/v1/rest/*')) {
            return $this->commission_fee * ($this->rate <= 0 ? 1 : $this->rate);
        }

        return $this->commission_fee;
    }

    public function getRateTotalPriceAttribute(): ?float
    {
        if (request()->is('api/v1/dashboard/user/*') || request()->is('api/v1/rest/*')) {
            return $this->total_price * ($this->rate <= 0 ? 1 : $this->rate);
        }

        return $this->total_price;
    }

    public function getRateDeliveryFeeAttribute(): ?float
    {
        if (request()->is('api/v1/dashboard/user/*') || request()->is('api/v1/rest/*')) {
            return $this->delivery_fee * ($this->rate <= 0 ? 1 : $this->rate);
        }

        return $this->delivery_fee;
    }

    public function getRateServiceFeeAttribute(): ?float
    {
        if (request()->is('api/v1/dashboard/user/*') || request()->is('api/v1/rest/*')) {
            return $this->service_fee * ($this->rate <= 0 ? 1 : $this->rate);
        }

        return $this->service_fee;
    }

    public function getRateTotalDiscountAttribute(): ?float
    {
        if (request()->is('api/v1/dashboard/user/*') || request()->is('api/v1/rest/*')) {
            return $this->total_discount * ($this->rate <= 0 ? 1 : $this->rate);
        }

        return $this->total_discount;
    }

    public function getRateCouponPriceAttribute(): ?float
    {
        if (request()->is('api/v1/dashboard/user/*') || request()->is('api/v1/rest/*')) {
            return $this->coupon_price * ($this->rate <= 0 ? 1 : $this->rate);
        }

        return $this->coupon_price;
    }

    public function getSellerFeeAttribute(): ?float
    {
        if (request()->is('api/v1/dashboard/user/*') || request()->is('api/v1/rest/*')) {
            return 0;
        }

        return $this->total_price
            - ($this->type == self::IN_HOUSE ? $this->delivery_fee : 0)
            - $this->service_fee
            - $this->commission_fee
            - $this->coupon_price;
    }

    public function getOriginPriceAttribute(): ?float
    {
        $originPrice = $this->total_price
            - $this->total_tax
            - $this->delivery_fee
            - $this->service_fee
            + $this->coupon_price
            + $this->total_discount;

        if (request()->is('api/v1/dashboard/user/*') || request()->is('api/v1/rest/*')) {
            return ($originPrice) * ($this->rate <= 0 ? 1 : $this->rate);
        }

        return $originPrice;
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function deliveryPrice(): BelongsTo
    {
        return $this->belongsTo(DeliveryPrice::class);
    }

    public function deliveryPoint(): BelongsTo
    {
        return $this->belongsTo(DeliveryPoint::class);
    }

    public function myAddress(): BelongsTo
    {
        return $this->belongsTo(UserAddress::class, 'address_id');
    }

    public function orderDetails(): HasMany
    {
        return $this->hasMany(OrderDetail::class);
    }

    public function deliveryman(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deliveryman_id');
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pointHistory(): HasOne
    {
        return $this->hasOne(PointHistory::class, 'order_id')->latest();
    }

    public function paymentToPartner(): MorphOne
    {
        return $this->morphOne(PaymentToPartner::class, 'model');
    }

    public function pointHistories(): HasMany
    {
        return $this->hasMany(PointHistory::class);
    }

    public function coupon(): HasOne
    {
        return $this->hasOne(OrderCoupon::class, 'order_id');
    }

    public function orderRefunds(): HasMany
    {
        return $this->hasMany(OrderRefund::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(OrderStatusNote::class, 'order_id');
    }

    public function logs(): MorphMany
    {
        return $this->morphMany(ModelLog::class, 'model');
    }

    /**
     * @param $query
     * @param array $filter
     * @return void
     */
    public function scopeFilter($query, array $filter): void
    {
        $column = $filter['column'] ?? 'id';

        if ($column !== 'id') {
            $column = Schema::hasColumn('orders', $column) ? $column : 'id';
        }

//        $orderByStatuses = [];
//
//        if (is_array(data_get($filter, 'statuses'))) {
//
//            $orderStatuses = OrderStatus::listNames();
//
//            if (count($orderStatuses) === 0) {
//                $orderStatuses = self::STATUSES;
//            }
//
//            $orderByStatuses = array_intersect($orderStatuses, data_get($filter, 'statuses'));
//        }

        $regionId   = data_get($filter, 'region_id');
        $countryId  = data_get($filter, 'country_id');
        $cityId     = data_get($filter, 'city_id');
        $areaId     = data_get($filter, 'area_id');
        $type       = data_get($filter, 'location_type');

        $byLocation = $regionId || $countryId || $cityId || $areaId;

        $shopIds = [];

        if ($byLocation && !data_get($filter, 'shop_id') && !data_get($filter, 'shop_ids')) {

            $shopIds = DB::table('shop_locations')
                ->where('region_id', $regionId)
                ->when($countryId, fn($q) => $q->where('country_id', $countryId))
                ->when($cityId,    fn($q) => $q->where('city_id', $cityId))
                ->when($areaId,    fn($q) => $q->where('area_id', $areaId))
                ->pluck('shop_id')
                ->unique()
                ->values()
                ->toArray();
        }

        $query
            ->when(isset($filter['current']),    fn($q) => $q->where('current', $filter['current']))
            ->when(isset($filter['type']),       fn($q) => $q->where('type', $filter['type']))
            ->when(isset($filter['parent']),     fn($q) => $filter['parent'] ? $q->whereNull('parent_id') : $q->whereNotNull('parent_id'))
            ->when(isset($filter['parent_id']),  fn($q) => $q->where('parent_id', $filter['parent_id']))
            ->when(isset($filter['track_name']), fn($q) => $q->where('track_name', $filter['track_name']))
            ->when(isset($filter['track_id']),   fn($q) => $q->where('track_id', $filter['track_id']))
            ->when(isset($filter['track_url']),  fn($q) => $q->where('track_url', $filter['track_url']))
            ->when(data_get($filter, 'isset-deliveryman'), function ($q) {
                $q->whereNotNull('deliveryman_id');
            })
            ->when(data_get($filter, 'shop_id'), fn($q, $shopId) => $q->where('shop_id', $shopId))
            ->when(data_get($filter, 'shop_ids'), fn($q, $shopIds) => $q->whereIn('shop_id', is_array($shopIds) ? $shopIds : []))
            ->when($byLocation && !isset($filter['shop_id']) && !isset($filter['shop_ids']), function ($q) use ($shopIds) {
                $q->whereIn('shop_id', $shopIds);
            })
            ->when(data_get($filter, 'payment_id'), function ($q, $paymentId) {
                $q->whereHas('transactions', fn($q) => $q->where('payment_sys_id', $paymentId));
            })
            ->when(data_get($filter, 'payment_status'), function ($q, $status) {
                $q->whereHas('transactions', fn($q) => $q->where('status', $status));
            })
            ->when(data_get($filter, 'user_id'), fn($q, $userId) => $q->where('user_id', (int)$userId))
            ->when(data_get($filter, 'delivery_type'), fn($q, $deliveryType) => $q->where('delivery_type', $deliveryType))
            ->when(data_get($filter, 'date_from'), function (Builder $query, $dateFrom) use ($filter) {

                $dateFrom = date('Y-m-d', strtotime($dateFrom));
                $dateTo = data_get($filter, 'date_to', date('Y-m-d'));

                $dateTo = date('Y-m-d', strtotime($dateTo));

                $query
                    ->whereDate('created_at', '>=', $dateFrom)
                    ->whereDate('created_at', '<=', $dateTo);
            })
            ->when(data_get($filter, 'delivery_date_from'), function (Builder $query, $dateFrom) use ($filter) {

                $dateFrom = date('Y-m-d', strtotime($dateFrom));

                $query->whereDate('delivery_date', '>=', $dateFrom);

                if (!empty(data_get($filter, 'delivery_date_to'))) {

                    $dateTo = date('Y-m-d', strtotime(data_get($filter, 'delivery_date_to')));

                    $query->whereDate('delivery_date', '<=', $dateTo);
                }
            })
            ->when(data_get($filter, 'status'), fn($q, $status) => $q->where('status', $status))
            ->when(data_get($filter, 'deliveryman_id'), fn(Builder $q, $deliverymanId) =>
                $q->where('deliveryman_id', $deliverymanId)
            )
            ->when(data_get($filter, 'empty-deliveryman'), fn(Builder $q) =>
                $q->whereNull('deliveryman_id')
            )
            ->when(data_get($filter, 'statuses'), fn($q, $statuses) => $q->whereIn('status', $statuses))
            ->when(data_get($filter, 'search'), function ($q, $search) {
                $q->where(function ($b) use ($search) {
                    $b
                        ->where('id', $search)
                        ->orWhere('user_id', $search)
                        ->orWhere('phone', "%$search%")
                        ->orWhere('username', "%$search%")
                        ->orWhere('note', 'LIKE', "%$search%")
                        ->orWhereHas('user', fn($q) => $this->search($q, $search));
                });
            })
            ->when(data_get($filter, 'order_statuses'), function ($q) {
                $q->orderByRaw(
                    DB::raw("FIELD(status, 'new', 'accepted', 'ready', 'on_a_way',  'delivered', 'canceled') DESC")
                );
            })
            ->orderBy($column, $filter['sort'] ?? 'desc');
    }

}
