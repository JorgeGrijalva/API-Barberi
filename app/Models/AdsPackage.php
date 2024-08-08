<?php
declare(strict_types=1);

namespace App\Models;

use App\Traits\ByLocation;
use App\Traits\Loadable;
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
 * App\Models\AdsPackage
 *
 * @property int $id
 * @property boolean $active
 * @property string $type
 * @property int $position_page
 * @property string $time_type
 * @property int $time
 * @property double $price
 * @property double $product_limit
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Collection|ShopAdsPackage[] $shopAdsPackages
 * @property int|null $shop_ads_packages_count
 * @property Collection|AdsPackageTranslation[] $translations
 * @property AdsPackageTranslation|null $translation
 * @property int|null $translations_count
 * @method static Builder|self active()
 * @method static Builder|self filter(array $filter)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereId($value)
 * @mixin Eloquent
 */
class AdsPackage extends Model
{
    use SetCurrency, Loadable, ByLocation;

    public $guarded = ['id'];

    protected $casts = [
        'active' => 'bool',
    ];

    // Package type
    const MAIN                  = 'main';
    const STANDARD              = 'standard';
    const MAIN_TOP_BANNER       = 'main_top_banner';
    const MAIN_BANNER           = 'main_banner';
    const MAIN_LEFT_BANNER      = 'main_left_banner';
    const MAIN_RIGHT_BANNER     = 'main_right_banner';
    const STANDARD_TOP_BANNER   = 'standard_top_banner';

    // Time type
    const MINUTE    = 'minute';
    const HOUR      = 'hour';
    const DAY       = 'day';
    const WEEK      = 'week';
    const MONTH     = 'month';
    const YEAR      = 'year';

    const TYPES     = [
        self::MAIN                  => self::MAIN,
        self::STANDARD              => self::STANDARD,
        self::MAIN_TOP_BANNER       => self::MAIN_TOP_BANNER,
        self::MAIN_BANNER           => self::MAIN_BANNER,
        self::MAIN_LEFT_BANNER      => self::MAIN_LEFT_BANNER,
        self::MAIN_RIGHT_BANNER     => self::MAIN_RIGHT_BANNER,
        self::STANDARD_TOP_BANNER   => self::STANDARD_TOP_BANNER,
    ];

    const PRODUCT_TYPES = [
        self::MAIN      => self::MAIN,
        self::STANDARD  => self::STANDARD,
    ];

    const TIME_TYPES = [
        self::MINUTE    => self::MINUTE,
        self::HOUR      => self::HOUR,
        self::DAY       => self::DAY,
        self::WEEK      => self::WEEK,
        self::MONTH     => self::MONTH,
        self::YEAR      => self::YEAR,
    ];

    public function translations(): HasMany
    {
        return $this->hasMany(AdsPackageTranslation::class);
    }

    public function translation(): HasOne
    {
        return $this->hasOne(AdsPackageTranslation::class);
    }

    public function shopAdsPackages(): HasMany
    {
        return $this->hasMany(ShopAdsPackage::class);
    }

    public function scopeActive($query): Builder
    {
        /** @var AdsPackage $query */
        return $query->where('active', true);
    }

    public function scopeFilter($query, array $filter): void
    {

        $regionId   = request('region_id');
        $countryId  = request('country_id');
        $cityId     = request('city_id');
        $areaId     = request('area_id');
        $byLocation = $regionId || $countryId || $cityId || $areaId;

        $column = data_get($filter, 'column','id');

        if ($column !== 'id') {
            $column = Schema::hasColumn('ads_packages', $column) ? $column : 'id';
        }

        $query
            ->when(isset($filter['active']), fn($q) => $q->where('active', $filter['active']))
            ->when(data_get($filter, 'type'), fn($q, $type) => $q->where('type', $type))
            ->when(data_get($filter, 'position_page'), fn($q, $positionPage) => $q->where('position_page', $positionPage))
            ->when(data_get($filter, 'time_type'), fn($q, $timeType) => $q->where('time_type', $timeType))
            ->when(data_get($filter, 'time'), fn($q, $time) => $q->where('time', $time))
            ->when($byLocation, fn($q) => $q->whereHas('shopAdsPackages', fn($q) => $q->whereIn('shop_id', $this->getShopIds($filter) )))
            ->when(data_get($filter, 'search'), function ($query, $search) {
                $query->whereHas('translations', function ($q) use ($search) {
                    $q->where('title', 'LIKE', "%$search%")->select('id', 'ads_package_id', 'locale', 'title');
                });
            })
            ->when(data_get($filter, 'price_from'), function ($query, $priceFrom) use ($filter) {
                $query
                    ->where('price', '>=', $priceFrom)
                    ->where('price', '<=', data_get($filter, 'price_to', 100000000));
            })
            ->when(data_get($filter, 'limit_from'), function ($query, $limitFrom) use ($filter) {
                $query
                    ->where('product_limit', '>=', $limitFrom)
                    ->where('product_limit', '<=', data_get($filter, 'limitTo', 1000000000));
            })
            ->orderBy($column, $filter['sort'] ?? 'desc');
    }
}
