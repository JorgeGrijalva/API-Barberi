<?php
declare(strict_types=1);

namespace App\Models;

use App\Traits\ByLocation;
use App\Traits\Loadable;
use App\Traits\MetaTagable;
use App\Traits\Reviewable;
use App\Traits\SetCurrency;
use Database\Factories\ProductFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Schema;

/**
 * App\Models\Product
 *
 * @property int $id
 * @property string $slug
 * @property string $uuid
 * @property int $category_id
 * @property int $brand_id
 * @property int|null $unit_id
 * @property int|null $min_qty
 * @property int|null $max_qty
 * @property bool $visibility
 * @property string|null $keywords
 * @property string|null $img
 * @property int|null $tax
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property string|null $qr_code
 * @property int $shop_id
 * @property boolean $active
 * @property string $status
 * @property string $status_note
 * @property boolean $digital
 * @property integer $age_limit
 * @property integer $r_count
 * @property float $r_avg
 * @property float $r_sum
 * @property integer $od_count
 * @property integer $o_count
 * @property double|null $min_price
 * @property double|null $max_price
 * @property double $interval
 * @property-read DigitalFile|null $digitalFile
 * @property-read Brand $brand
 * @property-read Collection|Tag[] $tags
 * @property-read Category $category
 * @property-read Stock|null $stock
 * @property-read Collection|Stock[] $stocks
 * @property-read int|null $stocks_count
 * @property-read Collection|Gallery[] $galleries
 * @property-read int|null $galleries_count
 * @property-read Collection|Story[] $stories
 * @property-read int|null $stories_count
 * @property-read int|null $product_sales_count
 * @property-read Collection|ProductProperty[] $properties
 * @property-read int|null $properties_count
 * @property-read Collection|Review[] $reviews
 * @property-read int|null $reviews_count
 * @property-read Shop|null $shop
 * @property-read ProductTranslation|null $translation
 * @property-read Collection|ProductTranslation[] $translations
 * @property-read int|null $translations_count
 * @property-read Unit|null $unit
 * @method static ProductFactory factory(...$parameters)
 * @method static Builder|self filter($filter)
 * @method static Builder|self active($active)
 * @method static Builder|self actual($lang)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereBrandId($value)
 * @method static Builder|self whereCategoryId($value)
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereImg($value)
 * @method static Builder|self whereKeywords($value)
 * @method static Builder|self whereQrCode($value)
 * @method static Builder|self whereUnitId($value)
 * @method static Builder|self whereUpdatedAt($value)
 * @method static Builder|self whereUuid($value)
 * @mixin Eloquent
 */
class Product extends Model
{
    use HasFactory, Loadable, Reviewable, SetCurrency, MetaTagable, ByLocation, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'active'     => 'boolean',
        'interval'   => 'double',
        'digital'    => 'boolean',
        'visibility' => 'boolean',
    ];

    const PUBLISHED     = 'published';
    const PENDING       = 'pending';
    const UNPUBLISHED   = 'unpublished';

    const STATUSES = [
        self::PUBLISHED     => self::PUBLISHED,
        self::PENDING       => self::PENDING,
        self::UNPUBLISHED   => self::UNPUBLISHED,
    ];

    // Translations
    public function translations(): HasMany
    {
        return $this->hasMany(ProductTranslation::class);
    }

    public function translation(): HasOne
    {
        return $this->hasOne(ProductTranslation::class);
    }

    // Product Shop
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function stories(): HasMany
    {
        return $this->hasMany(Story::class);
    }

    // Product Category
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    // Product Brand
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    // Product Properties
    public function properties(): HasMany
    {
        return $this->hasMany(ProductProperty::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function stock(): HasOne
    {
        return $this->hasOne(Stock::class);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    public function tags(): HasMany
    {
        return $this->hasMany(Tag::class);
    }

    public function digitalFile(): HasOne
    {
        return $this->hasOne(DigitalFile::class);
    }

    public function bannerProduct(): HasOne
    {
        return $this->hasOne(BannerProduct::class);
    }

    public function scopeActual($query, $lang)
    {
        $locale = Language::where('default', 1)->first()?->locale;

        $regionId   = request('region_id');
        $countryId  = request('country_id');
        $cityId     = request('city_id');
        $areaId     = request('area_id');
        $byLocation = $regionId || $countryId || $cityId || $areaId;

        $shopIds = [];

        if ($byLocation
            && (
                request()->is('api/v1/dashboard/user/*')
                || request()->is('api/v1/rest/*')
                || request()->is('api/v1/dashboard/likes')
            )
        ) {
            $shopIds = $this->getShopIds(request()->all());
        }

        return $query->where(function ($q) use ($byLocation, $lang, $locale, $shopIds) {
            $q
                ->whereHas('translation', function ($q) use ($lang, $locale) {
                    $q->where(fn($q) => $q->where('locale', $lang)->orWhere('locale', $locale));
                })
                ->when(
                    Settings::where('key', 'by_subscription')->first()?->value,
                    fn($q) => $q->whereHas('shop', fn ($query) => $query->where('visibility', true))
                )
                ->when($byLocation, fn($q) => $q->whereIn('shop_id', $shopIds))
                ->whereHas('stock', fn($q) => $q->where('quantity', '>', 0))
                ->where('active', true)
                ->where('status', Product::PUBLISHED);
//                ->where('digital', false);
        });
    }

    public function scopeFilter($query, array $filter)
    {
        $column  = $filter['column'] ?? 'id';

        if ($column !== 'id') {
            $column = Schema::hasColumn('products', $column) ? $column : 'id';
        }

//        $regionId   = data_get($filter, 'region_id');
//        $countryId  = data_get($filter, 'country_id');
//        $cityId     = data_get($filter, 'city_id');
//        $areaId     = data_get($filter, 'area_id');
//        $byLocation = $regionId || $countryId || $cityId || $areaId;

        $query
            ->when(isset($filter['has_discount']) || isset($filter['extras']), function ($q) use ($filter) {
                return $q->whereHas('stocks', function ($q) use ($filter) {

                    if (isset($filter['extras'])) {
                        $q->whereHas('stockExtras', function ($item) use ($filter) {
                            $item->whereIn('extra_value_id', $filter['extras']);
                        });
                    }

                    if (isset($filter['has_discount']) && $filter['has_discount'] == 1) {
                        $q->whereNotNull('discount_expired_at');
                    }

                    if (isset($filter['has_discount']) && $filter['has_discount'] == 0) {
                        $q->whereNull('discount_expired_at');
                    }

                    if (isset($filter['has_bonus']) && $filter['has_bonus'] == 1) {
                        $q->whereNotNull('bonus_expired_at');
                    }

                    if (isset($filter['has_bonus']) && $filter['has_bonus'] == 0) {
                        $q->whereNotNull('bonus_expired_at');
                    }

                });
            })
            ->when(data_get($filter, 'rating'), function (Builder $q, $rating) {

                $q
                    ->where('r_avg', '>=', data_get($rating, 0, 0))
                    ->where('r_avg', '<=', data_get($rating, 1, 5));

            })
//            ->when($byLocation, function ($query) use ($regionId, $countryId, $cityId, $areaId) {
//                $query->whereHas('shop.locations', function ($q) use ($regionId, $countryId, $cityId, $areaId) {
//                    $q->where('region_id', $regionId)
//                        ->orWhere('country_id', $countryId)
//                        ->orWhere('city_id', $cityId)
//                        ->orWhere('area_id', $areaId);
//                });
//            })
            ->when(data_get($filter, 'slug'), fn($q, $slug) => $q->where('slug', $slug))
            ->when(isset($filter['shop_id']), function ($q) use ($filter) {
                $q->where('shop_id', $filter['shop_id']);
            })
            ->when(isset($filter['banner_id']), function ($q) use ($filter) {
                $q->whereHas('bannerProduct', fn($q) => $q->where('banner_id', $filter['banner_id']));
            })
            ->when(isset($filter['not_in']), function ($q) use ($filter) {
                $q->whereNotIn('id', $filter['not_in']);
            })
            ->when(isset($filter['digital']), function ($q) use ($filter) {
                $q->where('digital', $filter['digital']);
            })
            ->when(isset($filter['category_id']), function ($q) use ($filter) {

                /** @var Category|null $category */
                $category = Category::with('children:id,parent_id')
                    ->select(['id'])
                    ->firstWhere('id', $filter['category_id']);

                $ids   = $category?->children?->pluck('id')?->toArray();
                $ids[] = $category?->id;

                $q->whereIn('category_id', $ids);
            })
            ->when(isset($filter['visibility']), function ($q) use ($filter) {
                $q->where('visibility', $filter['visibility']);
            })
            ->when(isset($filter['status']), function ($q) use ($filter) {
                $q->where('status', $filter['status']);
            })
            ->when(isset($filter['brand_id']), function ($q) use ($filter) {
                $q->where('brand_id', $filter['brand_id']);
            })
            ->when(isset($filter['column_rate']), function ($q) use ($filter) {
                $q->orderBy('r_avg', $filter['sort'] ?? 'desc');
            })
            ->when(data_get($filter, 'category_ids'), function ($q, $ids) {

                $categoryIds = DB::table('categories')
                    ->whereIn('id', $ids)
                    ->orWhereIn('parent_id', $ids)
                    ->select(['id', 'parent_id'])
                    ->pluck('id')
                    ->toArray();

                $q->whereIn('category_id', $categoryIds);
            })
            ->when(data_get($filter, 'shop_ids'), function ($q, $shopIds) {
                $q->whereIn('shop_id', $shopIds);
            })
            ->when(data_get($filter, 'brand_ids.*'), function ($q, $brandIds) {
                $q->whereIn('brand_id', $brandIds);
            })
            ->when(isset($filter['price_from']), function ($q) use ($filter) {

                $priceFrom = data_get($filter, 'price_from') / $this->currency();
                $priceTo   = data_get($filter, 'price_to', Product::max('max_price')) / $this->currency();

                $q->where('min_price', '>=', $priceFrom)->where('max_price', '<=', $priceTo);

            })
            ->when(isset($filter['rating_from']), function ($q) use ($filter) {
                $q
                    ->where('r_avg', '>=', data_get($filter, 'rating_from', 0))
                    ->where('r_avg', '<=', data_get($filter, 'rating_to',5));
            })
            ->when(isset($filter['age_limit_from']), function ($q) use ($filter) {
                $q
                    ->where('age_limit', '>=', data_get($filter, 'age_limit_from', 0))
                    ->where('age_limit', '<=', data_get($filter, 'age_limit_to',5));
            })
            ->when(isset($filter['active']), fn($query) => $query->where('active', $filter['active']))
            ->when(data_get($filter, 'date_from'), function (Builder $query, $dateFrom) use ($filter) {

                $dateFrom = date('Y-m-d 00:00:01', strtotime($dateFrom));
                $dateTo = data_get($filter, 'date_to', date('Y-m-d'));

                $dateTo = date('Y-m-d 23:59:59', strtotime($dateTo . ' +1 day'));

                $query->where([
                    ['created_at', '>=', $dateFrom],
                    ['created_at', '<=', $dateTo],
                ]);
            })
            ->when(data_get($filter, 'search'), function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query
                        ->where('keywords', 'LIKE', "%$search%")
                        ->orWhere('id', $search)
                        ->orWhere('uuid', $search)
                        ->orWhere('slug', 'LIKE', "%$search%")
                        ->orWhereHas('translation', function ($q) use($search) {
                            $q->where('title', 'LIKE', "%$search%")
                                ->select('id', 'product_id', 'locale', 'title');
                        });
                });
            })
            ->when(isset($filter['column_price']), function ($q) use ($filter) {
                $q->orderBy('max_price', $filter['sort'] ?? 'desc');
            })
            ->when(data_get($filter, 'shop_status'), function ($q, $status) {
                $q->whereHas('shop', function (Builder $query) use ($status) {
                    $query->select('id', 'status')->where('status', $status);
                });
            })
            ->when(
                data_get($filter, 'order_by'),
                function (Builder $query, $orderBy) {

                switch ($orderBy) {
                    case 'new':
                        $query->orderBy('created_at', 'desc');
                        break;
                    case 'old':
                        $query->orderBy('created_at');
                        break;
                    case 'best_sale':
                        $query->orderBy('od_count', 'desc');
                        break;
                    case 'low_sale':
                        $query->orderBy('od_count');
                        break;
                    case 'high_rating':
                        $query->orderBy('r_avg', 'desc');
                        break;
                    case 'low_rating':
                        $query->orderBy('r_avg');
                        break;
                    case 'trust_you':
                        $ids = implode(', ', array_keys(Cache::get('shop-recommended-ids', [])));
                        if (!empty($ids)) {
                            $query->orderByRaw(DB::raw("FIELD(shop_id, $ids) ASC"));
                        }
                        break;
                }

            },
                fn($q) => $q->orderBy($column, $filter['sort'] ?? 'desc')
            );
    }
}
