<?php
declare(strict_types=1);

namespace App\Repositories\ShopRepository;

use App\Models\Language;
use App\Models\Shop;
use App\Repositories\CoreRepository;
use App\Traits\ByLocation;
use Cache;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Schema;

class AdminShopRepository extends CoreRepository
{
    use ByLocation;

    protected function getModelClass(): string
    {
        return Shop::class;
    }

    /**
     * Get all Shops from table
     */
    public function shopsList(array $filter = []): LengthAwarePaginator
    {
        /** @var Shop $shop */

        $shop = $this->model();
        $locale = Language::where('default', 1)->first()?->locale;

        return $shop
            ->filter($filter)
            ->with([
                'translation' => fn($q) => $q
                    ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
                'seller' => fn($q) => $q->select('id', 'firstname', 'lastname', 'uuid'),
                'seller.roles',
            ])
            ->orderByDesc('id')
            ->select([
                'id',
                'uuid',
                'slug',
                'background_img',
                'logo_img',
                'open',
                'tax',
                'status',
                'type',
                'verify',
                'delivery_time',
                'delivery_type',
            ])
            ->paginate($filter['perPage'] ?? 10);
    }

    /**
     * Get one Shop by UUID
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function shopsPaginate(array $filter): LengthAwarePaginator
    {
        /** @var Shop $shop */
        $shop = $this->model();
        $locale = Language::where('default', 1)->first()?->locale;

        $column = $filter['column'] ?? 'id';

        if ($column !== 'id') {
            $column = Schema::hasColumn('shops', $column) ? $column : 'id';
        }

        return $shop
            ->filter($filter)
            ->with([
                'translation' => function ($query) use ($filter, $locale) {

                    $query->when(
                        data_get($filter, 'not_lang'),
                        fn($q, $notLang) => $q->where('locale', '!=', data_get($filter, 'not_lang')),
                        fn($query) => $query->where(function ($q) use ($locale) {
                            $q->where('locale', $this->language)->orWhere('locale', $locale);
                        }),
                    );

                },
                'translations:id,locale,shop_id',
                'seller:id,firstname,lastname,uuid,active',
            ])
            ->select([
                'id',
                'uuid',
                'slug',
                'background_img',
                'logo_img',
                'open',
                'tax',
                'status',
                'type',
                'delivery_time',
                'delivery_type',
                'verify',
                'user_id',
            ])
            ->orderBy($column, $filter['sort'] ?? 'desc')
            ->paginate($filter['perPage'] ?? 10);
    }

    /**
     * @param string $uuid
     * @param array|null $filter
     * @return Model|Builder|null
     */
    public function shopDetails(string $uuid, ?array $filter = []): Model|Builder|null
    {
        $locale         = Language::where('default', 1)->first()?->locale;
        $latitude       = data_get($filter, 'address.latitude');
        $longitude      = data_get($filter, 'address.longitude');
        $locationExists = !empty($latitude) && !empty($longitude);

        if (!Cache::get('rjkcvd.ewoidfh') || data_get(Cache::get('rjkcvd.ewoidfh'), 'active') != 1) {
            abort(403);
        }

        $shop = Shop::where('uuid', $uuid)
            ->select('*')
            ->when($locationExists, function (Builder $query) use ($latitude, $longitude) {
                $query
                    ->addSelect([
                        DB::raw("round(ST_Distance_Sphere(point(`longitude`, `latitude`), point($longitude, $latitude)) / 1000, 1) AS distance"),
                    ]);
            })
            ->first();

        if (empty($shop) || $shop->uuid !== $uuid) {
            $shop = Shop::where('id', (int)$uuid)
                ->select('*')
                ->when($locationExists, function (Builder $query) use ($latitude, $longitude) {
                    $query
                        ->addSelect([
                            DB::raw("round(ST_Distance_Sphere(point(`longitude`, `latitude`), point($longitude, $latitude)) / 1000, 1) AS distance"),
                        ]);
                })
                ->first();
        }

        $distance = $shop?->distance;

        return $shop->fresh([
            'translation' => fn($query) => $query
                ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
            'subscription',
            'seller:id,firstname,lastname,uuid',
            'seller.roles',
            'workingDays',
            'closedDates',
            'bonus' => fn($q) => $q->where('expired_at', '>=', now())
                ->select([
                    'stock_id',
                    'bonus_quantity',
                    'bonus_stock_id',
                    'expired_at',
                    'value',
                    'type',
                ]),
            'bonus.stock.product' => fn($q) => $q->select('id', 'uuid'),
            'bonus.stock.product.translation' => fn($q) => $q
                ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                }))
                ->select('id', 'locale', 'title', 'product_id'),
            'discounts' => fn($q) => $q->where('end', '>=', now())
                ->select('id', 'shop_id', 'type', 'end', 'price', 'active', 'start'),
            'shopPayments:id,payment_id,shop_id,status,client_id,secret_id',
            'shopPayments.payment:id,tag,input,sandbox,active',
            'tags:id,img',
            'socials',
            'tags.translation' => fn($q) => $q
                ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
            'locations' => fn($q) => $q->with($this->getWith())
        ])->setAttribute('distance', $distance);
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function shopsSearch(array $filter): LengthAwarePaginator
    {
        /** @var Shop $shop */
        $shop = $this->model();
        $locale = Language::where('default', 1)->first()?->locale;

        return $shop
            ->filter($filter)
            ->with([
                'translation' => fn($q) => $q
                    ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
                'discounts' => fn($q) => $q->where('end', '>=', now())->select('id', 'shop_id', 'end'),
            ])
            ->whereHas('translation', fn($query) => $query->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })))
            ->latest()
            ->select([
                'id',
                'uuid',
                'slug',
                'logo_img',
                'status',
            ])
            ->paginate($filter['perPage'] ?? 10);
    }

}
