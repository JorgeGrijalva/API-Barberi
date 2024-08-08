<?php
declare(strict_types=1);

namespace App\Repositories\FilterRepository;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Language;
use App\Models\Product;
use App\Models\Service;
use App\Models\Shop;
use App\Repositories\CoreRepository;
use App\Repositories\ShopRepository\ShopRepository;
use App\Traits\SetCurrency;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FilterRepository extends CoreRepository
{
    use SetCurrency;

    protected function getModelClass(): string
    {
        return Product::class;
    }

    /**
     * @param array $filter
     * @return array
     */
    public function filter(array $filter): array
    {
        $locale     = Language::where('default', 1)->first()?->locale;
        $lang       = data_get($filter, 'lang', $locale);
        $type       = data_get($filter, 'type');
        $column     = data_get($filter, 'column', 'id');

        $shops      = [];
        $brands     = [];
        $categories = [];
        $extras     = [];

        $min        = 0;
        $max        = 0;

        if ($column !== 'id') {
            $column = Schema::hasColumn('products', $column) ? $column : 'id';
        }

        if ($type === 'news_letter') {
            $column = 'created_at';
        }

        if ($type === 'most_sold') {
            $column = 'od_count';
        }

        $products = Product::filter($filter)
            ->actual($this->language)
            ->with([
                'brand:id,title,img,slug',
                'category:id,img,slug',
                'category.translation' => fn($q) => $q
                    ->where(function ($q) use($lang, $locale) {
                        $q->where('locale', $lang)->orWhere('locale', $locale);
                    })
                    ->select([
                        'id',
                        'category_id',
                        'locale',
                        'title',
                    ]),
                'shop:id,slug',
                'shop.translation' => fn($q) => $q
                    ->where(function ($q) use($lang, $locale) {
                        $q->where('locale', $lang)->orWhere('locale', $locale);
                    })
                    ->select([
                        'id',
                        'shop_id',
                        'locale',
                        'title',
                    ]),
                'stocks' => fn($q) => $q->where('quantity', '>', 0),
                'stocks.stockExtras' => fn($q) => $q->with([
                    'group:id,type',
                    'value:id,value',
                    'group.translation' => fn($q) => $q
                        ->where(function ($q) use($lang, $locale) {
                            $q->where('locale', $lang)->orWhere('locale', $locale);
                        })
                        ->select([
                            'id',
                            'extra_group_id',
                            'locale',
                            'title',
                        ]),
                ]),
            ])
            ->select([
                'id',
                'slug',
                'active',
                'status',
                'category_id',
                'brand_id',
                'shop_id',
                'brand_id',
                'min_price',
                'max_price',
                'r_avg',
                'age_limit',
                'od_count',
            ])
            ->when($type !== 'category', function ($query) {
                $query->limit(1000);
            })
            ->when($column, function ($query) use($column, $filter) {
                $query->orderBy($column, data_get($filter, 'sort', 'desc'));
            })
            ->lazy();

        foreach ($products as $product) {

            /** @var Product $product */
            $shop     = $product->shop;
            $brand    = $product->brand;
            $category = $product->category;
            $stocks   = $product->stocks;

            if ($shop?->id && $shop?->translation?->title) {
                $shops[$shop->id] = [
                    'id'    => $shop->id,
                    'slug'  => $shop->slug,
                    'title' => $shop->translation?->title
                ];
            }

            if ($brand?->id && $brand?->title) {
                $brands[$brand->id] = [
                    'id'    => $brand->id,
                    'slug'  => $brand->slug,
                    'img'   => $brand->img,
                    'title' => $brand->title,
                ];
            }

            if ($category?->id && $category?->translation?->title) {
                $categories[$category->id] = [
                    'id'    => $category->id,
                    'slug'  => $category->slug,
                    'img'   => $category->img,
                    'title' => $category->translation->title
                ];
            }

            foreach ($stocks as $stock) {

                foreach ($stock->stockExtras as $stockExtra) {

                    $value = $stockExtra->value;
                    $group = $stockExtra->group;

                    if (!$group?->id || !$value?->id) {
                        continue;
                    }

                    if (data_get($extras, $group->id)) {

                        $extras[$group->id]['extras'][$value->id] = [
                            'id'    => $value->id,
                            'value' => $value->value
                        ];

                        continue;
                    }

                    $extras[$group->id] = [
                        'id'     => $group->id,
                        'type'   => $group->type,
                        'title'  => $group->translation?->title,
                        'extras' => [
                            $value->id => [
                                'id'    => $value->id,
                                'value' => $value->value
                            ]
                        ]
                    ];

                }

            }

            if ($product->min_price < $min || $min == 0) {
                $min = $product->min_price;
            }

            if ($product->max_price > $max || $max == 0) {
                $max = $product->max_price;
            }

        }

        $groups = collect($extras)->map(function (array $items) {

            $items['extras'] = collect(data_get($items, 'extras', []))->sortDesc()->values()->toArray();

            return $items;
        })
            ->values()
            ->toArray();

        $categories = collect($categories)->sortDesc()->values()->toArray();

        return [
            'shops'       => collect($shops)->sortDesc()->values()->toArray(),
            'brands'      => collect($brands)->sortDesc()->values()->toArray(),
            'categories'  => $categories,
            'group'       => $groups,
            'price'       => [
                'min' => ($products->min('min_price') ?? 0) * $this->currency(),
                'max' => ($products->max('max_price') ?? 0) * $this->currency(),
            ],
            'count' => $products->count(),
        ];
    }

    public function search(array $filter): array
    {
        $locale = Language::where('default', 1)->first()?->locale;
        $search = data_get($filter, 'search');

        $productValues  = [];
        $categoryValues = [];
        $shopValues     = [];

        $productColumn  = data_get($filter, 'p_column', 'od_count');
        $categoryColumn = data_get($filter, 'c_column', 'id');
        $brandColumn    = data_get($filter, 'b_column', 'id');
        $shopColumn     = data_get($filter, 's_column', 'od_count');

        if ($productColumn !== 'od_count') {
            $productColumn = Schema::hasColumn('products', $productColumn) ? $productColumn : 'od_count';
        }

        if ($categoryColumn !== 'id') {
            $categoryColumn = Schema::hasColumn('categories', $categoryColumn) ? $categoryColumn : 'id';
        }

        if ($brandColumn !== 'id') {
            $brandColumn = Schema::hasColumn('brands', $brandColumn) ? $brandColumn : 'id';
        }

        if ($shopColumn !== 'id') {
            $shopColumn = Schema::hasColumn('shops', $shopColumn) ? $shopColumn : 'id';
        }

        $products = Product::whereHas('translation', fn($q) => $q
                ->select(['id', 'product_id', 'locale', 'title'])
                ->where('title', 'LIKE', "%$search%")
                ->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })
            )
            ->actual($this->language)
            ->select([
                'id',
                'active',
                'status',
                'digital',
                'shop_id',
            ])
            ->orderBy($productColumn, data_get($filter, 'p_sort', 'desc'))
            ->paginate(5)
            ->items();

        $categories = Category::whereHas('translation', fn($q) => $q
                ->select(['id', 'category_id', 'locale', 'title'])
                ->where('title', 'LIKE', "%$search%")
                ->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })
            )
            ->where('active', true)
            ->orderBy($categoryColumn, data_get($filter, 'c_sort', 'desc'))
            ->paginate(5)
            ->items();

        $brands = Brand::select(['id', 'title', 'active'])
            ->where('title', 'LIKE', "%$search%")
            ->where('active', true)
            ->orderBy($brandColumn, data_get($filter, 'b_sort', 'desc'))
            ->paginate(5)
            ->items();

        $shops = Shop::whereHas(
            'translation',
            fn($q) => $q
                ->select(['id', 'shop_id', 'locale', 'title'])
                ->where('title', 'LIKE', "%$search%")
                ->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })
            )
            ->where('status', Shop::APPROVED)
            ->orderBy($shopColumn, data_get($filter, 's_sort', 'desc'))
            ->paginate(5)
            ->items();

        foreach ($products as $product) {
            /** @var Product $product */
            $productValues[] = [
                'id'    => $product->id,
                'title' => $product->translation?->title
            ];
        }

        foreach ($categories as $category) {
            /** @var Category $category */
            $categoryValues[] = [
                'id'    => $category->id,
                'title' => $category->translation?->title
            ];
        }

        foreach ($shops as $shop) {
            /** @var Shop $shop */
            $shopValues[] = [
                'id'    => $shop->id,
                'title' => $shop->translation?->title
            ];
        }

        return [
            'products'   => $productValues,
            'categories' => $categoryValues,
            'brands'     => $brands,
            'shops'      => $shopValues,
        ];
    }

    public function searchMany(array $filter): array
    {
        $search = $filter['search'];
        $locale = Language::where('default', 1)->first()?->locale;

        $categoryValues = [];
        $shopValues     = [];
        $productValues  = [];
        $serviceValues  = [];

        $categoryColumn = data_get($filter, 'c_column',  'id');
        $shopColumn     = data_get($filter, 'sh_column', 'id');
        $productColumn  = data_get($filter, 'p_column',  'id');
        $serviceColumn  = data_get($filter, 's_column',  'id');

        if ($categoryColumn !== 'id') {
            $categoryColumn = Schema::hasColumn('categories', $categoryColumn) ? $categoryColumn : 'id';
        }

        if ($shopColumn !== 'id') {
            $shopColumn = Schema::hasColumn('shops', $shopColumn) ? $shopColumn : 'id';
        }

        if ($productColumn !== 'id') {
            $productColumn = Schema::hasColumn('products', $productColumn) ? $productColumn : 'id';
        }

        if ($serviceColumn !== 'id') {
            $serviceColumn = Schema::hasColumn('services', $serviceColumn) ? $serviceColumn : 'id';
        }

        $categories = Category::whereHas('translation', fn($q) => $q
            ->select(['id', 'category_id', 'locale', 'title', 'description'])
            ->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%$search%")->orWhere('description', 'LIKE', "%$search%");
            })
            ->where(function ($q) use ($locale) {
                $q->where('locale', $this->language)->orWhere('locale', $locale);
            }))
            ->where('active', true)
            ->orderBy($categoryColumn, data_get($filter, 'c_sort', 'desc'))
            ->paginate(5)
            ->items();

        $shops = Shop::whereHas('translation', fn($q) => $q
            ->select(['id', 'shop_id', 'locale', 'title', 'description'])
            ->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%$search%")->orWhere('description', 'LIKE', "%$search%");
            })
            ->where(function ($q) use ($locale) {
                $q->where('locale', $this->language)->orWhere('locale', $locale);
            }))
            ->where('status', Shop::APPROVED)
            ->orderBy($shopColumn, data_get($filter, 'sh_sort', 'desc'))
            ->paginate(5)
            ->items();

        $products = Product::whereHas('translation', fn($q) => $q
            ->select(['id', 'product_id', 'locale', 'title', 'description'])
            ->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%$search%")->orWhere('description', 'LIKE', "%$search%");
            })
            ->where(function ($q) use ($locale) {
                $q->where('locale', $this->language)->orWhere('locale', $locale);
            }))
            ->where('status', Product::PUBLISHED)
            ->where('active', true)
            ->orderBy($productColumn, data_get($filter, 'p_sort', 'desc'))
            ->paginate(5)
            ->items();

        $services = Service::whereHas('translation', fn($q) => $q
            ->select(['id', 'service_id', 'locale', 'title', 'description'])
            ->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%$search%")->orWhere('description', 'LIKE', "%$search%");
            })
            ->where(function ($q) use ($locale) {
                $q->where('locale', $this->language)->orWhere('locale', $locale);
            }))
            ->where('status', Service::STATUS_ACCEPTED)
            ->orderBy($serviceColumn, data_get($filter, 's_sort', 'desc'))
            ->paginate(5)
            ->items();

        foreach ($categories as $category) {
            /** @var Category $category */
            $categoryValues[] = [
                'id'          => $category->id,
                'uuid'        => $category->uuid,
                'slug'        => $category->slug,
                'img'         => $category?->img,
                'title'       => $category->translation?->title,
                'description' => $category->translation?->description
            ];
        }

        foreach ($shops as $shop) {
            /** @var Shop $shop */
            $shopValues[] = [
                'id'          => $shop->id,
                'uuid'        => $shop->uuid,
                'slug'        => $shop->slug,
                'img'         => $shop->logo_img,
                'title'       => $shop->translation?->title,
                'description' => $shop->translation?->description
            ];
        }

        foreach ($products as $product) {
            /** @var Product $product */
            $productValues[] = [
                'id'          => $product->id,
                'uuid'        => $product->uuid,
                'slug'        => $product->slug,
                'img'         => $product?->img,
                'title'       => $product->translation?->title,
                'description' => $product->translation?->description
            ];
        }

        foreach ($services as $service) {
            /** @var Service $service */
            $serviceValues[] = [
                'id'          => $service->id,
                'slug'        => $service->slug,
                'img'         => $service?->img,
                'title'       => $service->translation?->title,
                'description' => $service->translation?->description
            ];
        }

        return [
            'categories' => $categoryValues,
            'shops'      => $shopValues,
            'products'   => $productValues,
            'services'   => $serviceValues
        ];

    }

    public function shopFilter(array $filter = []): array
    {
        $services = Service::where('status', Service::STATUS_ACCEPTED)
            ->select([
                DB::raw('min(price) as service_min_price'),
                DB::raw('max(price) as service_max_price'),
//                DB::raw('min(interval) as interval_min'),
//                DB::raw('max(interval) as interval_max'),
            ])
            ->first();

        $categoryColumn = $filter['category_column'] ?? 'id';

        if ($categoryColumn !== 'id') {
            $categoryColumn = Schema::hasColumn('categories', $categoryColumn) ? $categoryColumn : 'id';
        }

        $locale = Language::where('default', 1)->first()?->locale;

        $categories = Category::with([
            'translation' => fn($q) => $q->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })
            ),
            'children' => fn($q) => $q->where('type', Category::SUB_SERVICE)
                ->where('active', true)
                ->where('status', Category::PUBLISHED),
            'children.translation' => fn($q) => $q->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })
            ),
        ])
            ->where('type', Category::SERVICE)
            ->where('active', true)
            ->where('status', Category::PUBLISHED)
            ->orderBy($categoryColumn, $filter['category_sort'] ?? 'desc')
            ->get();

        return [
            'order_by' => [
                'r_avg_asc'         => 'r_avg_asc',
                'r_avg_desc'        => 'r_avg_desc',
                'b_count_asc'       => 'b_count_asc',
                'b_count_desc'      => 'b_count_desc',
                'distance_asc'      => 'distance_asc',
                'distance_desc'     => 'distance_desc',
                'has_discount'      => 'has_discount',
                'service_min_price' => 'service_min_price', //sort - asc
                'service_max_price' => 'service_max_price', //sort - desc
            ],
            'service_type'      => Service::TYPES,
            'service_min_price' => $services?->service_min_price   ?? 0,
            'service_max_price' => $services?->service_max_price   ?? 0,
            'interval_min'      => Service::min('interval') ?? 0,
            'interval_max'      => Service::min('interval') ?? 0,
            'takes'             => (new ShopRepository)->takes(),
            'gender'            => Service::GENDERS,
            'categories'        => $categories,
        ];
    }
    // for cache filter
    //public function filter(array $filter): array
    //    {
    //        $key = $this->generateKey($filter);
    //
    //        $firstData  = Cache::get($key);
    //
    //        if ($firstData) {
    //            return $firstData;
    //        }
    //
    //        $secondData = Cache::get("{$key}_2");
    //
    //        if ($secondData) {
    //
    //            CashingFilter::dispatchAfterResponse($key, $filter);
    //
    //            return $secondData;
    //        }
    //
    //        return $this->cachingFilter($key, $filter);
    //    }
    //
    //    public function cachingFilter(string $key, array $filter) {
    //
    //        return Cache::remember($key, 1800, function () use ($filter, $key) {
    //
    //            $locale     = Language::where('default', 1)->first()?->locale;
    //            $lang       = data_get($filter, 'lang', $locale);
    //            $type       = data_get($filter, 'type');
    //            $column     = data_get($filter, 'column', 'id');
    //
    //            $shops      = [];
    //            $brands     = [];
    //            $categories = [];
    //            $extras     = [];
    //
    //            $min        = 0;
    //            $max        = 0;
    //
    //            if ($column !== 'id') {
    //                $column = Schema::hasColumn('products', $column) ? $column : 'id';
    //            }
    //
    //            if ($type === 'news_letter') {
    //                $column = 'created_at';
    //            }
    //
    //            if ($type === 'most_sold') {
    //                $column = 'od_count';
    //            }
    //
    //            $products = Product::filter($filter)
    //                ->actual($this->language)
    //                ->with([
    //                    'brand:id,title,img,slug',
    //                    'category:id,img,slug',
    //                    'category.translation' => fn($q) => $q
    //                        ->where(function ($q) use($lang, $locale) {
    //                            $q->where('locale', $lang)->orWhere('locale', $locale);
    //                        })
    //                        ->select([
    //                            'id',
    //                            'category_id',
    //                            'locale',
    //                            'title',
    //                        ]),
    //                    'shop:id,slug',
    //                    'shop.translation' => fn($q) => $q
    //                        ->where(function ($q) use($lang, $locale) {
    //                            $q->where('locale', $lang)->orWhere('locale', $locale);
    //                        })
    //                        ->select([
    //                            'id',
    //                            'shop_id',
    //                            'locale',
    //                            'title',
    //                        ]),
    //                    'stocks' => fn($q) => $q->where('quantity', '>', 0),
    //                    'stocks.stockExtras' => fn($q) => $q->with([
    //                        'group:id,type',
    //                        'value:id,value',
    //                        'group.translation' => fn($q) => $q
    //                            ->where(function ($q) use($lang, $locale) {
    //                                $q->where('locale', $lang)->orWhere('locale', $locale);
    //                            })
    //                            ->select([
    //                                'id',
    //                                'extra_group_id',
    //                                'locale',
    //                                'title',
    //                            ]),
    //                    ]),
    //                ])
    //                ->select([
    //                    'id',
    //                    'slug',
    //                    'active',
    //                    'status',
    //                    'category_id',
    //                    'brand_id',
    //                    'shop_id',
    //                    'brand_id',
    //                    'min_price',
    //                    'max_price',
    //                    'r_avg',
    //                    'age_limit',
    //                    'od_count',
    //                ])
    //                ->when($type !== 'category', function ($query) {
    //                    $query->limit(1000);
    //                })
    //                ->when($column, function ($query) use($column, $filter) {
    //                    $query->orderBy($column, data_get($filter, 'sort', 'desc'));
    //                })
    //                ->lazy();
    //
    //            foreach ($products as $product) {
    //
    //                /** @var Product $product */
    //                $shop     = $product->shop;
    //                $brand    = $product->brand;
    //                $category = $product->category;
    //                $stocks   = $product->stocks;
    //
    //                if ($shop?->id && $shop?->translation?->title) {
    //                    $shops[$shop->id] = [
    //                        'id'    => $shop->id,
    //                        'slug'  => $shop->slug,
    //                        'title' => $shop->translation?->title
    //                    ];
    //                }
    //
    //                if ($brand?->id && $brand?->title) {
    //                    $brands[$brand->id] = [
    //                        'id'    => $brand->id,
    //                        'slug'  => $brand->slug,
    //                        'img'   => $brand->img,
    //                        'title' => $brand->title,
    //                    ];
    //                }
    //
    //                if ($category?->id && $category?->translation?->title) {
    //                    $categories[$category->id] = [
    //                        'id'    => $category->id,
    //                        'slug'  => $category->slug,
    //                        'img'   => $category->img,
    //                        'title' => $category->translation->title
    //                    ];
    //                }
    //
    //                foreach ($stocks as $stock) {
    //
    //                    foreach ($stock->stockExtras as $stockExtra) {
    //
    //                        $value = $stockExtra->value;
    //                        $group = $stockExtra->group;
    //
    //                        if (!$group?->id || !$value?->id) {
    //                            continue;
    //                        }
    //
    //                        if (data_get($extras, $group->id)) {
    //
    //                            $extras[$group->id]['extras'][$value->id] = [
    //                                'id'    => $value->id,
    //                                'value' => $value->value
    //                            ];
    //
    //                            continue;
    //                        }
    //
    //                        $extras[$group->id] = [
    //                            'id'     => $group->id,
    //                            'type'   => $group->type,
    //                            'title'  => $group->translation?->title,
    //                            'extras' => [
    //                                $value->id => [
    //                                    'id'    => $value->id,
    //                                    'value' => $value->value
    //                                ]
    //                            ]
    //                        ];
    //
    //                    }
    //
    //                }
    //
    //                if ($product->min_price < $min || $min == 0) {
    //                    $min = $product->min_price;
    //                }
    //
    //                if ($product->max_price > $max || $max == 0) {
    //                    $max = $product->max_price;
    //                }
    //
    //            }
    //
    //            $groups = collect($extras)->map(function (array $items) {
    //
    //                $items['extras'] = collect(data_get($items, 'extras', []))->sortDesc()->values()->toArray();
    //
    //                return $items;
    //            })
    //                ->values()
    //                ->toArray();
    //
    //            $categories = collect($categories)->sortDesc()->values()->toArray();
    //
    //            $result = [
    //                'shops'       => collect($shops)->sortDesc()->values()->toArray(),
    //                'brands'      => collect($brands)->sortDesc()->values()->toArray(),
    //                'categories'  => $categories,
    //                'group'       => $groups,
    //                'price'       => [
    //                    'min' => ($products->min('min_price') ?? 0) * $this->currency(),
    //                    'max' => ($products->max('max_price') ?? 0) * $this->currency(),
    //                ],
    //                'count' => $products->count(),
    //            ];
    //
    //            Cache::remember("{$key}_2", 1860, fn() => $result);
    //
    //            return $result;
    //        });
    //    }
}
