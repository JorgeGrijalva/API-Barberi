<?php
declare(strict_types=1);

namespace App\Repositories\ProductRepository;

use App\Helpers\Utility;
use App\Http\Resources\ProductResource;
use App\Jobs\UserActivityJob;
use App\Models\Language;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\ShopAdsPackage;
use App\Repositories\CoreRepository;
use App\Traits\ByLocation;
use Closure;
use DB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Schema;

class RestProductRepository extends CoreRepository
{
    use ByLocation;

    protected function getModelClass(): string
    {
        return Product::class;
    }

    /**
     * @return Closure[]
     */
    public function with(): array
    {
        $locale = Language::where('default', 1)->first()?->locale;

        return [
            'translation' => fn($query) => $query->where('locale', $this->language)->orWhere('locale', $locale),
            'stocks',
            'stocks.gallery',
            'stocks.stockExtras.value',
            'stocks.stockExtras.group.translation' => fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale),
            'stocks.bonus' => fn($q) => $q
                ->where('expired_at', '>', now())
                ->select(['id', 'expired_at', 'stock_id', 'bonus_quantity', 'value', 'type', 'status']),
            'stocks.discount' => fn($q) => $q
                ->where('start', '<=', today())
                ->where('end', '>=', today())
                ->where('active', 1),
        ];
    }

    /**
     * @return Closure[]
     */
    public function showWith(): array
    {
        $locale = Language::where('default', 1)->first()?->locale;

        return [
            'shop.translation' => fn($q) => $q
                ->where('locale', $this->language)->orWhere('locale', $locale),
            'category' => fn($q) => $q->select('id', 'uuid'),
            'category.translation' => fn($q) => $q
                ->where('locale', $this->language)->orWhere('locale', $locale)
                ->select('id', 'category_id', 'locale', 'title'),
            'brand',
            'unit.translation' => fn($q) => $q
                ->where('locale', $this->language)->orWhere('locale', $locale),
            'translation' => fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale),
            'galleries' => fn($q) => $q->select('id', 'type', 'loadable_id', 'path', 'title', 'preview'),
            'properties.group.translation' => fn($q) => $q
                ->where('locale', $this->language)->orWhere('locale', $locale),
            'properties.value',
            'stocks',
            'stocks.galleries',
            'stocks.stockExtras.value',
            'stocks.stockExtras.group.translation' => fn($q) => $q
                ->where('locale', $this->language)->orWhere('locale', $locale),
            'stocks.wholeSalePrices',
        ];
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function productsPaginate(array $filter): LengthAwarePaginator
    {
        /** @var Product $product */
        $product = $this->model();

        return $product
            ->filter($filter)
            ->actual($this->language)
            ->with($this->with())
            ->paginate($filter['perPage'] ?? 10);
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function productsDiscount(array $filter = []): LengthAwarePaginator
    {
        /** @var Product $product */
        $product = $this->model();

        return $product
            ->filter($filter)
            ->actual($this->language)
            ->with($this->with())
            ->paginate($filter['perPage'] ?? 10);
    }

    /**
     * @param string $uuid
     * @return Product|null
     */
    public function productByUUID(string $uuid): ?Product
    {
        /** @var Product $product */
        $product = $this->model();
        $locale  = Language::where('default', 1)->first()?->locale;

        return $product
            ->whereHas(
                'translation',
                fn($query) => $query->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                }))
            )
            ->with($this->showWith())
            ->where('active', true)
            ->where('status', Product::PUBLISHED)
            ->where('uuid', $uuid)
            ->first();
    }

    /**
     * @param string $slug
     * @return Product|null
     */
    public function productBySlug(string $slug): ?Product
    {
        /** @var Product $product */
        $product = $this->model();
        $locale  = Language::where('default', 1)->first()?->locale;

        return $product
            ->whereHas('translation', fn($query) => $query->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                }))
            )
            ->with($this->showWith())
            ->where('active', true)
            ->where('status', Product::PUBLISHED)
            ->where('slug', $slug)
            ->first();
    }

    /**
     * @param int $id
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function alsoBought(int $id, array $filter): LengthAwarePaginator
    {
        $stocksIds = DB::table('stocks')
            ->select(['id', 'product_id'])
            ->where('product_id', $id)
            ->pluck('id', 'id')
            ->toArray();

        $lastMonth = date('Y-m-d', strtotime('-1 month'));

        $orderDetails = OrderDetail::with([
            'stock:id,product_id',
        ])
            ->select([
                'stock_id',
                'created_at'
            ])
            ->whereIn('stock_id', $stocksIds)
            ->whereDate('created_at', '>=', $lastMonth)
            ->get()
            ->pluck('stock.product_id', 'stock.product_id')
            ->toArray();

        /** @var Product $model */
        $model = $this->model();

        return $model
            ->filter($filter)
            ->actual($this->language)
            ->with($this->with())
            ->whereIn('id', $orderDetails)
            ->where('id', '!=', $id)
            ->paginate($filter['perPage'] ?? 10);
    }

    /**
     * @param string $uuid
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function related(string $uuid, array $filter): LengthAwarePaginator
    {
        /** @var Product $product */
        $product = $this->model();
        $locale  = Language::where('default', 1)->first()?->locale;

        $column = $filter['column'] ?? 'id';

        if ($column !== 'id') {
            $column = Schema::hasColumn('products', $column) ? $column : 'id';
        }

        $related = Product::firstWhere('uuid', $uuid);

        if ($related?->id) {
            UserActivityJob::dispatchAfterResponse(
                $related->id,
                get_class($related),
                'click',
                1,
                auth('sanctum')->user()
            );
        }

        return $product
            ->actual($this->language)
            ->with([
                'unit.translation' => fn($q) => $q
                    ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
                'translation' => fn($query) => $query
                    ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
                'stocks',
                'stocks.gallery',
                'stocks.stockExtras.value',
                'stocks.stockExtras.group.translation' => fn($q) => $q
                    ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
            ])
            ->where('id', '!=', $related?->id)
            ->where(function ($query) use ($related) {
                $query
                    ->where('category_id', $related?->category_id)
                    //->orWhere('shop_id', $related?->shop_id)
                    ->orWhere('brand_id', $related?->brand_id);
            })
            ->orderBy($column, $filter['sort'] ?? 'desc')
            ->paginate($filter['perPage'] ?? 10);
    }

    public function adsPaginate(array $filter): LengthAwarePaginator
    {
        $adsPage   = data_get($filter, 'page', 1);
        $locale    = Language::where('default', 1)->first()?->locale;

        $regionId   = request('region_id');
        $countryId  = request('country_id');
        $cityId     = request('city_id');
        $areaId     = request('area_id');
        $byLocation = $regionId || $countryId || $cityId || $areaId;

        return ShopAdsPackage::with([
            'shopAdsProducts.product' => fn($q) => $q
                ->whereHas('translation', fn($query) => $query
                    ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                        $q->where('locale', $this->language)->orWhere('locale', $locale);
                    }))
                )
                ->with([
                    'translation' => fn($q) => $q
                        ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                            $q->where('locale', $this->language)->orWhere('locale', $locale);
                        })),
                    'stocks' => fn($q) => $q
                        ->with([
                            'gallery',
                            'bonus' => fn($q) => $q
                                ->where('expired_at', '>', now())
                                ->select([
                                    'id', 'expired_at', 'stock_id',
                                    'bonus_quantity', 'value', 'type', 'status'
                                ]),
                            'discount' => fn($q) => $q
                                ->where('start', '<=', today())
                                ->where('end', '>=', today())
                                ->where('active', 1),
                            'stockExtras.value',
                            'stockExtras.group.translation' => fn($q) => $q
                                ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                                })),
                        ])
                ]),
        ])
            ->when($byLocation, fn($q) => $q->whereIn('shop_id', $this->getShopIds($filter)))
            ->where('active', true)
            ->where('status', ShopAdsPackage::APPROVED)
            ->whereDate('expired_at', '>', date('Y-m-d H:i:s'))
            ->paginate($filter['perPage'] ?? 10, page: $adsPage);
    }

    /**
     * @param array $filter
     * @return Model|\Illuminate\Database\Eloquent\Builder|Product|Collection|null
     */
    public function productsByIDs(array $filter = []): Model|Builder|Product|Collection|null
    {
        $locale = Language::where('default', 1)->first()?->locale;

        $ids = data_get($filter, 'products', []);

        $toStringIds = implode(', ' , $ids);

        return $this->model()
            ->filter($filter)
            ->actual($this->language)
            ->with([
                'translation' => fn($q) => $q
                    ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                        $q->where('locale', $this->language)->orWhere('locale', $locale);
                    }))
                    ->select('id', 'product_id', 'locale', 'title'),
                'stocks.gallery',
                'stocks.stockExtras.value',
                'stocks.stockExtras.group.translation' => fn($q) => $q
                    ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                        $q->where('locale', $this->language)->orWhere('locale', $locale);
                    })),
            ])
            ->whereIn('id', $ids)
            ->orderByRaw(\Illuminate\Support\Facades\DB::raw("FIELD(id, $toStringIds)"))
            ->get();
    }

    /**
     * @param array $filter
     * @return mixed
     */
    public function compare(array $filter = []): mixed
    {
        $locale = Language::where('default', 1)->first()?->locale;

        $products = Product::actual($this->language)
            ->with([
                'properties.group.translation' => fn($q) => $q
                    ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
                'properties.value',
                'stocks',
                'stocks.gallery',
                'stocks.stockExtras.value',
                'stocks.stockExtras.group.translation' => fn($q) => $q
                    ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
                'stocks.discount' => fn($q) => $q
                    ->where('start', '<=', today())
                    ->where('end', '>=', today())
                    ->where('active', 1),
                'stocks.bonus' => fn($q) => $q
                    ->where('expired_at', '>', now())
                    ->select([
                        'id', 'expired_at', 'stock_id',
                        'bonus_quantity', 'value', 'type', 'status'
                    ]),
                'translation' => fn($query) => $query
                    ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
                'category' => fn($q) => $q->select('id', 'uuid'),
                'category.translation' => fn($q) => $q
                    ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                }))
                    ->select('id', 'category_id', 'locale', 'title'),
                'brand' => fn($q) => $q->select('id', 'uuid', 'title'),
            ])
            ->select([
                'id',
                'slug',
                'uuid',
                'shop_id',
                'category_id',
                'brand_id',
                'unit_id',
                'keywords',
                'img',
                'qr_code',
                'tax',
                'active',
                'status',
                'min_qty',
                'max_qty',
                'age_limit',
                'interval',
                'min_price',
                'max_price',
                'r_count',
                'r_avg',
                'r_sum',
                'o_count',
                'od_count',
            ])
            ->find($filter['ids'])
            ->map(function (Product $product) {
                return ProductResource::make($product);
            });

        return $products->groupBy('category_id')->values();
    }

    /**
     * @param int $id
     * @return array
     */
    public function reviewsGroupByRating(int $id): array
    {
        return Utility::reviewsGroupRating([
            'reviewable_type' => Product::class,
            'reviewable_id'   => $id,
        ]);
    }
}
