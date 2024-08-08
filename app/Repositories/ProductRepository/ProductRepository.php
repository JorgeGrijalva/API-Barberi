<?php
declare(strict_types=1);

namespace App\Repositories\ProductRepository;

use App\Models\Language;
use App\Models\Product;
use App\Models\Shop;
use App\Models\Stock;
use App\Repositories\CoreRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ProductRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return Product::class;
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function productsPaginate(array $filter): LengthAwarePaginator
    {
        /** @var Product $product */
        $product = $this->model();
        $locale  = Language::where('default', 1)->first()?->locale;

        return $product
            ->filter($filter)
            ->with([
                'digitalFile',
                'shop' => fn($q) => $q->select('id', 'uuid', 'user_id', 'logo_img', 'background_img', 'type', 'status'),
                'shop.translation' => fn($q) => $q
                    ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                }))
                    ->select('id', 'locale', 'title', 'shop_id'),
                'translation' => fn($q) => $q
                    ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
                'translations',
                'category' => fn($q) => $q->select('id', 'uuid'),
                'category.translation' => fn($q) => $q
                    ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                }))
                    ->select('id', 'category_id', 'locale', 'title'),
                'brand' => fn($q) => $q->select('id', 'uuid', 'title'),
                'unit.translation' => fn($query) => $query->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
                'tags.translation' => fn($q) => $q->select('id', 'category_id', 'locale', 'title')
                    ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
                'stocks.gallery',
                'stocks.stockExtras.value',
                'stocks.stockExtras.group.translation' => fn($query) => $query->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
            ])
            ->paginate($filter['perPage'] ?? 10);
    }

    /**
     * @param int $id
     * @return Model|Builder|Product|null
     */
    public function productDetails(int $id): Model|Builder|Product|null
    {
        $locale = Language::where('default', 1)->first()?->locale;

        return $this->model()
            ->whereHas('translation', fn($query) => $query->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })))
            ->with([
                'shop.translation' => fn($q) => $q
                    ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
                'category' => fn($q) => $q->select('id', 'uuid'),
                'category.translation' => fn($q) => $q
                    ->select('id', 'category_id', 'locale', 'title')
                    ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
                'brand' => fn($q) => $q->select('id', 'uuid', 'title'),
                'unit.translation' => fn($q) => $q
                    ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
                'translation' => fn($query) => $query->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
                'tags.translation' => fn($q) => $q
                    ->select('id', 'category_id', 'locale', 'title')
                    ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
                'galleries' => fn($q) => $q->select('id', 'type', 'loadable_id', 'path', 'title', 'preview'),
                'properties.group.translation' => fn($q) => $q
                    ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
                'properties.value',
                'stocks.galleries',
                'stocks.stockExtras.group.translation' => fn($q) => $q
                    ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
        ])->find($id);
    }

    /**
     * @param string $uuid
     * @return Product|Builder|Model|null
     */
    public function productByUUID(string $uuid): Model|Builder|Product|null
    {
        /** @var Product $product */
        $product = $this->model();
        $locale = Language::where('default', 1)->first()?->locale;

        return $product
            ->with([
                'digitalFile',
                'shop.translation' => fn($q) => $q
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
                'unit.translation' => fn($q) => $q
                    ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
                'translation' => fn($query) => $query
                    ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
                'tags.translation' => fn($q) => $q
                    ->select('id', 'category_id', 'locale', 'title')
                    ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
                'galleries' => fn($q) => $q->select('id', 'type', 'loadable_id', 'path', 'title', 'preview'),
                'properties.group.translation' => fn($q) => $q
                    ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
                'properties.value',
                'stocks.galleries',
                'stocks.stockExtras.value',
                'stocks.stockExtras.group.translation' => fn($q) => $q
                    ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
                'stocks.wholeSalePrices',
            ])
            ->firstWhere('uuid', $uuid);
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function productsSearch(array $filter = []): LengthAwarePaginator
    {
        $locale = Language::where('default', 1)->first()?->locale;

        return $this->model()
            ->filter($filter)
            ->with([
                'translation' => fn($q) => $q
                    ->select([
                        'id',
                        'product_id',
                        'locale',
                        'title',
                    ])
                    ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
            ])
            ->whereHas('shop', fn ($query) => $query->where('status', Shop::APPROVED))
            ->whereHas('stock', fn($q) => $q->where('quantity', '>', 0))
            ->latest()
            ->select([
                'id',
                'img',
                'shop_id',
                'uuid',
            ])
            ->paginate($filter['perPage'] ?? 10);
    }


    /**
     * @param array $data
     * @return LengthAwarePaginator
     */
    public function selectStockPaginate(array $data): LengthAwarePaginator
    {
        $locale  = Language::where('default', 1)->first()?->locale;

        return Stock::with([
            'gallery',
            'stockExtras.value',
            'stockExtras.group.translation' => fn($q) => $q
                ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
            'product' => fn($q) => $q->select(['id', 'shop_id']),
            'product.translation' => fn($q) => $q
                ->select('id', 'product_id', 'locale', 'title')
                ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
        ])
            ->whereHas('product', fn($q) => $q
                ->whereHas(
                    'translation',
                    fn($query) => $query->where(function ($q) use ($locale) {
                        $q->where('locale', $this->language)->orWhere('locale', $locale);
                    })
                )
                ->where('shop_id', data_get($data, 'shop_id') )
                ->when(isset($data['active']), fn($q) => $q->where('active', $data['active']))
                ->when(data_get($data, 'status'), fn($q, $status) => $q->where('status', $status))
                ->when(data_get($data, 'search'), function ($q, $search) {

                    $q->where(function ($query) use ($search) {
                        $query
                            ->where('keywords', 'LIKE', "%$search%")
                            ->orWhereHas('translation', function ($q) use ($search) {
                                $q->where('title', 'LIKE', "%$search%")->select('id', 'product_id', 'locale', 'title');
                            });
                    });

                })
            )
            ->where('quantity', '>', 0)
            ->paginate($data['perPage'] ?? 10);
    }

}

