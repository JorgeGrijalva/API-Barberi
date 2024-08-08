<?php
declare(strict_types=1);

namespace App\Repositories\ProductRepository;

set_time_limit(0);
ini_set('memory_limit', '4G');

use App\Exports\ProductReportExport;
use App\Exports\StockReportExport;
use App\Helpers\ResponseError;
use App\Models\Language;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\UserActivity;
use App\Repositories\CoreRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Jenssegers\Agent\Agent;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Exception;
use Schema;
use Throwable;

class ProductReportRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return Product::class;
    }

    /**
     * @param array $filter
     * @return array|Paginator
     */
    public function productReportPaginate(array $filter): Paginator|array
    {
        try {
            $dateFrom = date('Y-m-d 00:00:01', strtotime(data_get($filter, 'date_from')));
            $dateTo   = date('Y-m-d 23:59:59', strtotime(data_get($filter, 'date_to', now()->toString())));
            $locale   = Language::where('default', 1)->first(['locale', 'default'])?->locale;
            $shopId   = data_get($filter, 'shop_id');

            $data = OrderDetail::with([
                'stock' => fn($q) => $q->with([
                    'product:id,active',
                    'product.translation' => fn($q) => $q
                        ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                            $q->where('locale', $this->language)->orWhere('locale', $locale);
                        }))
                        ->select('id', 'locale', 'product_id', 'title'),
                    'stockExtras.value',
                ])
            ])
                ->when($shopId, fn($q) => $q->whereHas('order', fn($q) => $q->where('shop_id', $shopId)))
                ->whereDate('created_at', '>=', $dateFrom)
                ->whereDate('created_at', '<=', $dateTo)
                ->select([
                    DB::raw('stock_id'),
                    DB::raw('count(id) as count'),
                    DB::raw('sum(total_price) as total_price'),
                    DB::raw('sum(quantity) as quantity'),
                ])
                ->groupBy('stock_id');

            if (data_get($filter, 'export') === 'excel') {

                $name = 'products-report-' . Str::random(8);

                $data = $data->get();

                Excel::store(
                    new ProductReportExport($data),
                    "export/$name.xlsx",
                    'public',
                    \Maatwebsite\Excel\Excel::XLSX
                );

                return [
                    'status' => true,
                    'code'   => ResponseError::NO_ERROR,
                    'data'   => [
                        'path'      => 'public/export',
                        'file_name' => "export/$name.xlsx",
                        'link'      => URL::to("storage/export/$name.xlsx"),
                    ]
                ];

            }

            $data = $data->paginate($filter['perPage'] ?? 10);

            $items = collect($data->items())->transform(function ($item) {

                $extras  = collect($item->stock?->stockExtras)
                    ->pluck('value.value')
                    ->toArray();

                $extras = implode(', ', $extras);

                $item->title    = $item->stock?->product?->translation?->title;
                $item->quantity = (int)$item->quantity;
                $item->active   = $item?->active ? 'active' : 'inactive';

                if (!empty($extras)) {
                    $item->title .= " $extras";
                }

                unset($item['stock']);
                unset($item['stock_id']);

                return $item;
            });

            return [
                'data'       => $items,
                'last_page'  => $data->lastPage(),
                'page'       => $data->currentPage(),
                'total'      => $data->total(),
                'more_pages' => $data->hasMorePages(),
                'has_pages'  => $data->hasPages(),
            ];

        } catch (Throwable $e) {
            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_400,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * @param array $filter
     * @return array|LengthAwarePaginator
     * @throws Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function stockReportPaginate(array $filter): LengthAwarePaginator|array
    {
        $locale = Language::where('default', 1)->first()?->locale;
        $column = $filter['column'] ?? 'id';

        if ($column !== 'id') {
            $column = Schema::hasColumn('products', $column) ? $column : 'id';
        }

        $query = Product::filter($filter)
            ->with([
                'translation' => fn($q) => $q
                    ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                        $q->where('locale', $this->language)->orWhere('locale', $locale);
                    }))
                    ->select('id', 'product_id', 'locale', 'title'),
                'stocks:id,product_id,quantity',
            ])
            ->when(is_array(data_get($filter, 'products')), function (Builder $query) use($filter) {
                $query->whereIn('id', data_get($filter, 'products'));
            })
            ->when(is_array(data_get($filter, 'categories')), function (Builder $query) use($filter) {
                $query->whereIn('category_id', data_get($filter, 'categories'));
            })
            ->when(data_get($filter, 'actual'), function ($query, $actual) {

                if ($actual === 'in_stock') {
                    $query->whereHas('stocks', fn($q) => $q->where('quantity', '>', 0));
                } else if ($actual === 'low_stock') {
                    $query->whereHas('stocks', fn($q) => $q->where('quantity', '<=', 10)->where('quantity', '>', 0));
                } else if ($actual === 'out_of_stock') {
                    $query->whereHas('stocks', fn($q) => $q->where('quantity', '<=', 0));
                }

                return $query;
            })
            ->select([
                'id',
                'status',
                'shop_id',
                'keywords'
            ])
            ->withSum('stocks', 'quantity')
            ->orderBy($column, $filter['sort'] ?? 'desc');

        if (data_get($filter, 'export') === 'excel') {

            $name = 'stocks-report-' . Str::random(8);

            Excel::store(new StockReportExport($query->get()), "export/$name.xlsx",'public', \Maatwebsite\Excel\Excel::XLSX);

            return [
                'path'      => 'public/export',
                'file_name' => "export/$name.xlsx",
                'link' => URL::to("storage/export/$name.xlsx"),
            ];
        }

        return $query->paginate($filter['perPage'] ?? 10);
    }

    /**
     * @param array $filter
     * @return array|LengthAwarePaginator
     */
    public function extrasReportPaginate(array $filter): LengthAwarePaginator|array
    {
        if (data_get($filter, 'export') === 'excel') {

            $name = 'products-report-' . Str::random(8);

            return [
                'path' => 'public/export',
                'file_name' => "export/$name.xlsx",
                'link' => URL::to("storage/export/$name.xlsx"),
            ];
        }

        $locale = Language::where('default', 1)->first()?->locale;
        $column = $filter['column'] ?? 'id';

        if ($column !== 'id') {
            $column = Schema::hasColumn('products', $column) ? $column : 'id';
        }

        return Product::with([
            'translation' => fn($q) => $q
                ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                }))
                ->select('id', 'product_id', 'locale', 'title'),
            'stocks.stockExtras.value',
            'stocks.stockExtras.group.translation' => fn($q) => $q
                ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
            'stocks.orderProducts.orderDetail.order:id,total_price',
        ])
            ->when(data_get($filter, 'products'), fn($q, $ids) => $q->whereIn('id', $ids))
            ->when(data_get($filter, 'categories'), fn($q, $ids) => $q->whereIn('category_id', $ids))
            ->when(data_get($filter, 'shop_id'), fn($q, $id) => $q->where('shop_id', $id))
            ->select([
                'id',
                'price',
                'quantity',
            ])
            ->whereHas('orderDetails', fn($q) => $q->withSum('order', 'total_price'))
            ->withSum('orderDetails', 'quantity')
            ->orderBy($column, $filter['sort'] ?? 'desc')
            ->paginate($filter['perPage'] ?? 10);
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function history(array $filter): LengthAwarePaginator
    {
        $agent = new Agent;

        $where = [
            'model_type' => Product::class,
            'device' => $agent->device(),
            'ip' => request()->ip(),
        ];

        if (data_get($filter, 'user_id')) {

            $where = [
                'model_type' => Product::class,
                'device'     => $agent->device(),
                'user_id'    => data_get($filter, 'user_id'),
            ];

        }

        $ids = UserActivity::where($where)->pluck('model_id')->unique()->toArray();

        return Product::actual($this->language)
            ->whereIn('id', $ids)
            ->where('id', '!=', data_get($filter, 'id'))
            ->with((new RestProductRepository)->with())
            ->paginate($filter['perPage'] ?? 10);
    }

    public function mostPopulars(array $filter): LengthAwarePaginator
    {
        $locale = Language::where('default', 1)->first()?->locale;

        $filter['model_type'] = Product::class;

        if (data_get($filter, 'date_from')) {
            $filter['date_from']  = date('Y-m-d 00:00:01', strtotime(data_get($filter, 'date_from')));
        }

        if (data_get($filter, 'date_to')) {
            $filter['date_to']  = date('Y-m-d 00:00:01', strtotime(data_get($filter, 'date_to')));
        }

        return UserActivity::filter($filter)
            ->with([
                'model.translation' => fn($q) => $q
                    ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                        $q->where('locale', $this->language)->orWhere('locale', $locale);
                    }))
            ])
            ->select([
                'model_type',
                'model_id',
                DB::raw('count(model_id) as count'),
            ])
            ->groupBy('model_id', 'model_type')
            ->orderBy('count', 'desc')
            ->paginate($filter['perPage'] ?? 10);
    }
}

