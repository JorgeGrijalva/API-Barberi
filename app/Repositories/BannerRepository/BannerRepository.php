<?php
declare(strict_types=1);

namespace App\Repositories\BannerRepository;

use App\Models\Banner;
use App\Models\Language;
use App\Repositories\CoreRepository;
use App\Traits\ByLocation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Schema;

class BannerRepository extends CoreRepository
{
    use ByLocation;

    protected function getModelClass(): string
    {
        return Banner::class;
    }

    public function bannersPaginate(array $filter): LengthAwarePaginator
    {
        $locale  = Language::where('default', 1)->first()?->locale;
        $shopIds = $this->getShopIds($filter);
        $column  = data_get($filter, 'column','id');

        if ($column !== 'id') {
            $column = Schema::hasColumn('banners', $column) ? $column : 'id';
        }

        if (!isset($filter['type'])) {
            $filter['type'] = Banner::BANNER;
        }

        /** @var Banner $model */
        $model = $this->model();

        if (isset($filter['shop_id'])) {
            $shopIds = [(int)$filter['shop_id']];
        }

        $regionId   = request('region_id');
        $countryId  = request('country_id');
        $cityId     = request('city_id');
        $areaId     = request('area_id');
        $byLocation = $regionId || $countryId || $cityId || $areaId;

        return $model
            ->with([
                'translation' => fn($q) => $q
                    ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
            ])
            ->when(request()->is('api/v1/rest/*'), function ($query) use ($filter, $shopIds, $byLocation) {
                $query->whereHas('products', fn($q) => $q
                    ->actual($this->language)
                    ->when($byLocation || count($shopIds) > 0, function ($q) use ($shopIds) {
                        $q->whereIn('shop_id', $shopIds);
                    })
                    ->when(data_get($filter, 'product_ids'), function ($q, $productIds) {
                        $q->whereIn('id', $productIds);
                    })
                    ->whereHas('stock', fn($q) => $q->where('quantity', '>', 0))
                );
            })
            ->when(
                ($byLocation || count($shopIds) > 0) && $filter['type'] === Banner::LOOK,
                function ($q) use ($shopIds) {
                    $q->whereIn('shop_id', $shopIds);
                }
            )
            ->when(data_get($filter, 'active'), function ($q, $active) {
                $q->where('active', $active);
            })
            ->when(
                data_get($filter, 'type'),
                fn($q, $type) => $q->where('type', $type), fn($q) => $q->where('type', Banner::BANNER)
            )
            ->select([
                'id',
                'url',
                'type',
                'shop_id',
                'img',
                'active',
                'created_at',
                'updated_at',
                'clickable',
            ])
            ->withCount('likes')
            ->withCount('products')
            ->orderBy($column, $filter['sort'] ?? 'desc')
            ->paginate($filter['perPage'] ?? 10);
    }

    public function bannerDetails(int $id, array $filter = []): Model|null
    {
        $locale = Language::where('default', 1)->first()?->locale;

        return $this->model()
            ->withCount('likes')
            ->withCount('products')
            ->with([
                'galleries',
                'shop:id,logo_img',
                'shop.translation' => fn($q) => $q
                    ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
                'products' => fn($q) => $q
                    ->actual($this->language)
                    ->with([
                        'translation' => fn($query) => $query
                            ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
                        'stocks' => fn($q) => $q
                            ->with([
                                'bonus' => fn($q) => $q->where('expired_at', '>', now())->select([
                                    'id', 'expired_at', 'stock_id',
                                    'bonus_quantity', 'value', 'type', 'status'
                                ]),
                                'stockExtras.group.translation' => fn($q) => $q
                                    ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
                                'discount' => fn($q) => $q
                                    ->where('start', '<=', today())
                                    ->where('end', '>=', today())
                                    ->where('active', 1),
                            ])
                            ->where('quantity', '>', 0),
                    ]),
                'translation' => fn($query) => $query
                    ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
                'translations',
            ])
            ->when(request()->is('api/v1/rest/*'), function ($query) {
                $query->whereHas('products.stock', fn($q) => $q->where('quantity', '>', 0));
            })
            ->find($id);
    }
}
