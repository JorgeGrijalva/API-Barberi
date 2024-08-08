<?php
declare(strict_types=1);

namespace App\Models;

use App\Traits\Payable;
use App\Traits\SetCurrency;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Schema;

/**
 * App\Models\ShopAdsPackage
 *
 * @property int $id
 * @property boolean $active
 * @property int $ads_package_id
 * @property int $shop_id
 * @property string $status
 * @property Carbon|null $expired_at
 * @property AdsPackage|null $adsPackage
 * @property-read PaymentProcess|null $paymentProcess
 * @property ShopAdsProduct|null $shopAdsProduct
 * @property Collection|ShopAdsProduct[] $shopAdsProducts
 * @property int|null $shop_ads_products_count
 * @property-read Transaction|null $transaction
 * @method static Builder|self active()
 * @method static Builder|self filter(array $filter)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereId($value)
 * @mixin Eloquent
 */
class ShopAdsPackage extends Model
{
    use SetCurrency, Payable;

    public $guarded     = ['id'];
    public $timestamps  = false;

    protected $casts    = [
        'active' => 'bool',
    ];

    const NEW       = 'new';
    const APPROVED  = 'approved';
    const CANCELED  = 'canceled';

    const STATUSES  = [
        self::NEW       => self::NEW,
        self::APPROVED  => self::APPROVED,
        self::CANCELED  => self::CANCELED,
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function adsPackage(): BelongsTo
    {
        return $this->belongsTo(AdsPackage::class);
    }

    public function shopAdsProduct(): HasOne
    {
        return $this->hasOne(ShopAdsProduct::class);
    }

    public function shopAdsProducts(): HasMany
    {
        return $this->hasMany(ShopAdsProduct::class);
    }

    public function paymentProcess(): MorphOne
    {
        return $this->morphOne(PaymentProcess::class, 'model');
    }

    public function scopeActive($query): Builder
    {
        return $query->where('active', true);
    }

    public function scopeFilter($query, array $filter): void
    {
        $column = $filter['column'] ?? 'id';

        if ($column !== 'id') {
            $column = Schema::hasColumn('shop_ads_packages', $column) ? $column : 'id';
        }

        $query
            ->when(data_get($filter, 'ads_package_id'), fn($q, $id) => $q->where('ads_package_id', $id))
            ->when(data_get($filter, 'shop_id'),        fn($q, $id) => $q->where('shop_id', $id))
            ->when(isset($filter['active']),                fn($q)      => $q->where('active', $filter['active']))
            ->orderBy($column, $filter['sort'] ?? 'desc');
    }
}
