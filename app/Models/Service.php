<?php
declare(strict_types=1);

namespace App\Models;

use App\Traits\Loadable;
use App\Traits\SetCurrency;
use Carbon\Carbon;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

/**
 * App\Models\Service
 * @property int $id
 * @property string $slug
 * @property string $type
 * @property int $category_id
 * @property int $shop_id
 * @property string $status
 * @property string $status_note
 * @property string $img
 * @property double $price
 * @property int $interval
 * @property int $pause
 * @property double $commission_fee
 * @property array $data
 * @property int $gender
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read double $rate_price
 * @property-read double $total_price
 * @property-read double $rate_total_price
 * @property-read double $rate_commission_fee
 * @property-read Collection|ServiceTranslation[] $translations
 * @property-read Collection|ServiceExtra[] $serviceExtras
 * @property-read int $translations_count
 * @property-read ServiceTranslation $translation
 * @property-read Category $category
 * @property-read Shop $shop
 * @property-read ServiceMaster $serviceMaster
 * @property-read Collection|ServiceMaster[] $serviceMasters
 * @property-read double|null $service_masters_count
 * @method static Builder|self actual()
 * @method static Builder|self filter(array $filter)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereId($value)
 * @mixin Eloquent
 */
class Service extends Model
{
    use Loadable, SetCurrency;

    public $guarded = ['id'];

    protected $casts = [
        'data' => 'array',
    ];

    const STATUS_NEW       = 'new';
    const STATUS_ACCEPTED  = 'accepted';
    const STATUS_CANCELED  = 'canceled';

    const STATUSES = [
        self::STATUS_NEW      => self::STATUS_NEW,
        self::STATUS_ACCEPTED => self::STATUS_ACCEPTED,
        self::STATUS_CANCELED => self::STATUS_CANCELED,
    ];

//    const ALL         = 'all';
    const ONLINE      = 'online';
    const OFFLINE_IN  = 'offline_in';
    const OFFLINE_OUT = 'offline_out';

    const TYPES = [
//        self::ALL         => self::ALL,
        self::ONLINE      => self::ONLINE,
        self::OFFLINE_IN  => self::OFFLINE_IN,
        self::OFFLINE_OUT => self::OFFLINE_OUT,
    ];

    const MALE        = 1;
    const FEMALE      = 2;
    const ALL_GENDERS = 3;

    const GENDERS = [
        self::MALE        => self::MALE,
        self::FEMALE      => self::FEMALE,
        self::ALL_GENDERS => self::ALL_GENDERS
    ];

    public function getRatePriceAttribute(): float|int|null
    {
        if (request()->is('api/v1/dashboard/user/*') || request()->is('api/v1/rest/*')) {
            return $this->price * $this->currency();
        }

        return $this->price;
    }

    public function getTotalPriceAttribute(): float|int|null
    {
        return $this->price + $this->commission_fee;
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

    public function translations(): HasMany
    {
        return $this->hasMany(ServiceTranslation::class);
    }

    public function translation(): HasOne
    {
        return $this->hasOne(ServiceTranslation::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function serviceMaster(): HasOne
    {
        return $this->hasOne(ServiceMaster::class);
    }

    public function serviceMasters(): HasMany
    {
        return $this->hasMany(ServiceMaster::class);
    }

    public function serviceExtras(): HasMany
    {
        return $this->hasMany(ServiceExtra::class);
    }

    public function serviceFaqs(): HasMany
    {
        return $this->hasMany(ServiceFaq::class);
    }

    public function scopeActual($query)
    {
        return $query->where('status', self::STATUS_ACCEPTED);
    }

    public function scopeFilter($query, array $filter) {

        if (isset($filter['empty_shop'])) {
            unset($filter['shop_id']);
            unset($filter['shop_ids']);
        }

        $query
            ->when(request()->is('api/v1/rest/*'), fn($q)            => $q->actual())
            ->when(data_get($filter, 'type'),          fn($q, $type)     => $q->where('type', $type))
            ->when(data_get($filter, 'category_id'),   fn($q, $id)       => $q->where('category_id', $id))
            ->when(data_get($filter, 'shop_id'),       fn($q, $id)       => $q->where('shop_id', $id))
            ->when(data_get($filter, 'empty_shop'),    fn($q)            => $q->whereNull('shop_id'))
            ->when(data_get($filter, 'status'),        fn($q, $status)   => $q->where('status', $status))
            ->when(data_get($filter, 'price_from'),    fn($q, $price)    => $q->where('price', '>=', $price))
            ->when(data_get($filter, 'price_to'),      fn($q, $price)    => $q->where('price', '<=', $price))
            ->when(data_get($filter, 'interval_from'), fn($q, $interval) => $q->where('interval', '>=', $interval))
            ->when(data_get($filter, 'interval_to'),   fn($q, $interval) => $q->where('interval', '<=', $interval))
            ->when(data_get($filter, 'pause_from'),    fn($q, $pause)    => $q->where('pause', '>=', $pause))
            ->when(data_get($filter, 'pause_to'),      fn($q, $pause)    => $q->where('pause', '<=', $pause))
            ->when(data_get($filter, 'start_from'),    fn($q, $time)     => $q->whereDate('start_time', '>=', $time))
            ->when(data_get($filter, 'start_to'),      fn($q, $time)     => $q->whereDate('start_time', '<=', $time))
            ->when(data_get($filter, 'end_from'),      fn($q, $time)     => $q->whereDate('end_time', '>=', $time))
            ->when(data_get($filter, 'end_to'),        fn($q, $time)     => $q->whereDate('end_time', '<=', $time))
            ->when(data_get($filter, 'gender'),        fn($q, $gender)   => $q->where('gender', $gender))
            ->when(isset($filter['shop_ids']),             fn($q) => $q->whereIn('shop_id', $filter['shop_ids']))
            ->when(data_get($filter, 'master_ids'),    function ($q, $ids) {
                return $q->whereHas('serviceMasters', fn($q) => $q->whereIn('master_id', $ids));
            })
            ->when(data_get($filter, 'master_id'),    function ($q, $id) {
                return $q->whereHas('serviceMasters', fn($q) => $q->where('master_id', $id));
            })
            ->when(data_get($filter, 'has_master'), function ($q) {
                return $q
                    ->whereHas('serviceMaster', fn($q) => $q->where('active', true))
                    ->has('serviceMaster.master.workingDays')
                    ->whereHas('serviceMaster.master.invitations', function ($q) {
                        $q->where('status', 2);
                    });
            })
            ->when(data_get($filter, 'search'), fn($q, $search) => $q->where(function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query
                        ->whereHas('translation', fn($q) => $q->where(fn($q) => $q->where('title', 'like', "%$search%")))
                        ->orWhere('status_note', 'like', "%$search%");
                });
            }));
    }
}
