<?php
declare(strict_types=1);

namespace App\Repositories\OrderRepository;

set_time_limit(0);
ini_set('memory_limit', '4G');

use App\Exports\OrdersReportExport;
use App\Exports\OrdersRevenueReportExport;
use App\Helpers\OrderHelper;
use App\Helpers\ResponseError;
use App\Http\Resources\OrderResource;
use App\Models\Bonus;
use App\Models\CategoryTranslation;
use App\Models\Language;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\PaymentToPartner;
use App\Models\Settings;
use App\Models\Shop;
use App\Models\Stock;
use App\Models\WholeSalePrice;
use App\Repositories\CoreRepository;
use App\Repositories\ReportRepository\ChartRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class OrderReportRepository extends CoreRepository
{
    /**
     * @return string
     */
    protected function getModelClass(): string
    {
        return Order::class;
    }

    /**
     * @param array $filter
     * @return array
     */
    public function orderStocksCalculate(array $filter): array
    {
        $locale   = Language::where('default', 1)->first()?->locale;
        $products = collect(data_get($filter, 'products', []));

        $result = [];

        /** @var Stock $stock */
        $stocks = Stock::with([
            'product' => fn($q) => $q->select([
                'id',
                'uuid',
                'shop_id',
                'unit_id',
                'keywords',
                'img',
                'qr_code',
                'tax',
                'min_qty',
                'max_qty',
                'interval',
            ]),
            'discount' => fn($q) => $q
                ->where('start', '<=', today())
                ->where('end', '>=', today())
                ->where('active', 1),
            'galleries',
            'product.galleries',
            'product.translation' => fn($q) => $q
                ->select('id', 'product_id', 'locale', 'title')
                ->when($this->language, function ($q) use ($locale) {
                    $q->where(fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale));
                }),
            'product.unit.translation' => fn($q) => $q
                ->when($this->language, function ($q) use ($locale) {
                    $q->where(fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale));
                }),
            'stockExtras.value',
            'stockExtras.group.translation' => fn($q) => $q
                ->when($this->language, function ($q) use ($locale) {
                    $q->where(fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale));
                }),
            'wholeSalePrices',
        ])
            ->find($products->pluck('stock_id')->toArray());

        foreach ($stocks as $stock) {

            $product  = $products->where('stock_id', $stock->id)->first();

            $quantity = (int)OrderHelper::actualQuantity($stock, $product['quantity'] ?? null) ?? 0;

            if (!isset($product['quantity']) || $product['quantity'] === 0 || $quantity === 0) {
                continue;
            }

            $wholeSalePrice = $stock->wholeSalePrices
                ?->where('min_quantity', '<=', $quantity)
                ?->where('max_quantity', '>=', $quantity)
                ?->first();

            $price    = $stock->rate_price;
            $discount = ($stock->rate_actual_discount * $quantity);
            $tax      = ($stock->rate_tax_price * $quantity);

            if (!empty($wholeSalePrice)) {
                /** @var WholeSalePrice $wholeSalePrice */
                $price    = $wholeSalePrice->price;
                $discount = 0;
                $tax      = 0;
            }

            $price *= $quantity;
            $totalPrice = $price - $discount + $tax;

            $result[] = [
                'id'               => $stock->id,
                'shop_id'          => $stock->product?->shop_id,
                'price'            => $price + $tax,
                'product_price'    => $stock->rate_total_price,
                'quantity'         => $quantity,
                'product_quantity' => $stock->quantity,
                'tax'              => $tax,
                'discount'         => $discount,
                'total_price'      => $totalPrice,
                'image'            => data_get($product, 'image'),
                'stock'            => $stock->toArray(),
            ];

            /** @var Bonus $bonus */
            $bonus = Bonus::with([
                'bonusStock',
                'bonusStock.product' => fn($q) => $q->select([
                    'id',
                    'uuid',
                    'shop_id',
                    'unit_id',
                    'keywords',
                    'img',
                    'qr_code',
                    'tax',
                    'min_qty',
                    'max_qty',
                    'interval',
                ]),
                'bonusStock.product.translation' => fn($q) => $q
                    ->select('id', 'product_id', 'locale', 'title')
                    ->when($this->language, function ($q) use ($locale) {
                        $q->where(fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale));
                    }),
                'bonusStock.discount' => fn($q) => $q
                    ->where('start', '<=', today())
                    ->where('end', '>=', today())
                    ->where('active', 1),
                'bonusStock.stockExtras.value',
                'bonusStock.stockExtras.group.translation' => fn($q) => $q
                    ->when($this->language, function ($q) use ($locale) {
                        $q->where(fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale));
                    }),
                'bonusStock.product.unit.translation' => fn($q) => $q
                    ->when($this->language, function ($q) use ($locale) {
                        $q->where(fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale));
                    }),
            ])
                ->where([
                    ['stock_id', $stock->id],
                    ['expired_at', '>', now()]
                ])
                ->whereHas('bonusStock', fn($q) => $q->where('quantity', '>', 0))
                ->first();

            if ($bonus && $quantity >= data_get($bonus, 'value', 0)) {

                $bonusQuantity = (int)($bonus->type === Bonus::TYPE_COUNT ?
                    $bonus->bonus_quantity * floor($quantity / $bonus->value) :
                    $bonus->bonus_quantity);

                $bonusQuantity = OrderHelper::actualQuantity($bonus->bonusStock, $bonusQuantity, true);

                if (empty($bonusQuantity)) {
                    continue;
                }

                $result[] = [
                    'id'                => $bonus->bonusStock->id,
                    'shop_id'           => $stock->product?->shop_id,
                    'price'             => 0,
                    'product_price'     => 0,
                    'quantity'          => $bonusQuantity,
                    'product_quantity'  => $bonus->bonusStock->quantity,
                    'tax'               => 0,
                    'discount'          => 0,
                    'total_price'       => 0,
                    'bonus'             => true,
                    'image'             => data_get($product, 'image'),
                    'stock'             => $bonus->bonusStock,
                ];
            }

        }

        $result = collect(array_values($result));

        $shopData    = [];
        $errors      = [];
        $deliveryFee = [];
        $couponPrice = collect();

        foreach (collect($result)->groupBy('shop_id') as $shopId => $item) {

            /**@var Shop $shop */
            $shop = Shop::with([
                'translation' => fn($q) => $q->when($this->language, function ($q) use ($locale) {
                    $q->where(fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale));
                }),
            ])
                ->select([
                    'id',
                    'tax',
                    'uuid',
                    'logo_img',
                    'delivery_type',
                    'delivery_time',
                ])
                ->find($shopId);

            try {
                if (collect($deliveryFee)->where('shop_id', $shopId)->isEmpty()) {
                    OrderHelper::checkShopDelivery($shop, $filter, $this->language, $deliveryFee);
                }
            } catch (Throwable $e) {
                $errors[] = [
                    'shop_id' => $shopId,
                    'message' => $e->getMessage(),
                ];
            }

            $totalPrice = collect($item)->sum('total_price');
            $discount   = collect($item)->sum('discount');
            $price      = collect($item)->sum('price');
            $tax        = collect($item)->sum('tax');

            $shopTax = max((($totalPrice) / $this->currency() / 100 * $shop->tax) * $this->currency(), 0);

            $shopData[$shopId] = [
                'price'       => $price,
                'total_price' => $totalPrice,
                'tax'         => $shopTax,
                'product_tax' => $tax,
                'discount'    => $discount,
                'shop'        => $shop,
                'stocks'      => $item,
            ];

            $coupon = $couponPrice->where('shop_id', $shopId)->first();

            if ($coupon?->price <= 0) {
                $couponPrice = OrderHelper::checkCoupon($filter, $shopId, $totalPrice, $this->currency(), $couponPrice, $deliveryFee);
            }

        }

        $shopData   = collect(array_values($shopData));

        $totalPrice = $shopData->sum('total_price');
        $shopTax    = $shopData->sum('tax');
        $price      = $shopData->sum('price');

        $serviceFee = (double)Settings::where('key', 'service_fee')->first()?->value ?: 0;

        if ($serviceFee > 0) {
            $serviceFee *= $this->currency();
        }

        $totalPrice += $serviceFee;
        $totalPrice += $shopTax;

        $deliveryFeeSum = collect($deliveryFee)->sum('price');

        $couponPriceSum = collect($couponPrice)->sum('price');

        $data = [
            'shops'          => $shopData,
            'total_tax'      => round($shopTax, 2),
            'price'          => $price,
            'total_price'    => round(max($totalPrice + $deliveryFeeSum - $couponPriceSum, 0), 2),
            'service_fee'    => $serviceFee,
            'total_discount' => $shopData->sum('discount'),
            'delivery_fee'   => $deliveryFee,
            'rate'           => $this->currency(),
            'coupon'         => $couponPrice,
        ];

        if (count($errors) > 0) {
            $data['errors'] = $errors;
        }

        return $data;
    }

    /**
     * @param array $filter
     * @return array
     */
    public function ordersReportChart(array $filter): array
    {
        $dateFrom = date('Y-m-d 00:00:01', strtotime(data_get($filter, 'date_from')));
        $dateTo   = date('Y-m-d 23:59:59', strtotime(data_get($filter, 'date_to', now()->toString())));

        $type = data_get($filter, 'type');

        $keys  = ['count', 'price', 'quantity'];
        $chart = data_get($filter, 'chart');
        $chart = in_array($chart, $keys) ? $chart : 'count';

        $type = match ($type) {
            'year'  => '%Y',
            'week'  => '%w',
            'month' => '%Y-%m',
            default => '%Y-%m-%d',
        };

        $orders = Order::when(data_get($filter, 'shop_id'), function ($query, $shopId) {
            $query->where('shop_id', $shopId);
        })
            ->where('status', Order::STATUS_DELIVERED)
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->select([
                DB::raw("(DATE_FORMAT(created_at, '$type')) as time"),
                DB::raw('total_price as price'),
            ])
            ->withSum('orderDetails', 'quantity')
            ->get();

        $result = [];

        foreach ($orders as $order) {

            $time = data_get($order, 'time');

            if (data_get($result, $time)) {
                $result[$time]['count']     += 1;
                $result[$time]['price']     += data_get($order, 'price', 0);
                $result[$time]['quantity']  += data_get($order, 'order_details_sum_quantity', 0);
                continue;
            }

            $result[$time] = [
                'time'     => $time,
                'count'    => 1,
                'price'    => data_get($order, 'price', 0),
                'quantity' => data_get($order, 'order_details_sum_quantity', 0),
            ];

        }

        $result = collect(array_values($result));

        $count     = max($result->sum('count'), 0);
        $price     = max($result->sum('price'), 0);
        $avgPrice  = $price > 0 && $count > 0 ? $price / $count : 0;
        $quantity  = max($result->sum('quantity'), 0);

        return [
            'chart'     => ChartRepository::chart($result, $chart),
            'currency'  => $this->currency,
            'count'     => $count,
            'price'     => $price,
            'avg_price' => $avgPrice,
            'quantity'  => $quantity,
        ];
    }

    /**
     * @param array $filter
     * @return array
     */
    public function orderReportTransaction(array $filter): array
    {
        $dateFrom = date('Y-m-d 00:00:01', strtotime(data_get($filter, 'date_from', '-30 days')));
        $dateTo   = date('Y-m-d 23:59:59', strtotime(data_get($filter, 'date_to', now())));
        $shopId   = data_get($filter, 'shop_id');

        $tax 			= 0;
        $coupon			= 0;
        $pointHistory	= 0;
        $commissionFee  = 0;
        $deliveryFee 	= 0;
        $serviceFee 	= 0;
        $totalPrice 	= 0;

        $orders = Order::with([
            'transaction.paymentSystem:id,tag',
            'coupon',
            'pointHistories',
            data_get($filter, 'type') === PaymentToPartner::SELLER ? 'shop.seller' : 'deliveryman',
        ])
            ->where([
                ['created_at', '>=', $dateFrom],
                ['created_at', '<=', $dateTo],
                ['status', Order::STATUS_DELIVERED]
            ])
            ->when($shopId, fn($q, $shopId) => $q->where('shop_id', $shopId))
            ->when(data_get($filter, 'type'), function($q, $type) use ($filter) {

                if ($type === PaymentToPartner::DELIVERYMAN) {
                    $q->whereHas('deliveryman', function ($q) use ($filter) {
                        $q->when(data_get($filter, 'user_id'), fn($q, $userId) => $q->where('id', $userId));
                    });
                } else if ($type === PaymentToPartner::SELLER) {
                    $q->whereHas('shop', function ($q) use ($filter) {

                        $q
                            ->whereHas('seller')
                            ->when(data_get($filter, 'user_id'), fn($q, $userId) => $q->where('user_id', $userId));
                    });
                }

                $q->whereDoesntHave('paymentToPartner', fn($q) => $q->where('type', $type));

            })
            ->withSum('pointHistories', 'price');

        $orders->chunkMap(
            function (Order $order) use (
                &$tax,
                &$coupon,
                &$pointHistory,
                &$commissionFee,
                &$deliveryFee,
                &$serviceFee,
                &$totalPrice,
        ) {
            if ($order->type == Order::IN_HOUSE) {
                $deliveryFee += $order->delivery_fee;
            }

            $totalPrice     += $order->total_price;
            $serviceFee     += $order->service_fee;
            $tax            += $order->total_tax;
            $coupon         += $order->coupon_price;
            $pointHistory   += $order->point_histories_sum_price;
            $commissionFee  += $order->commission_fee;
        });

        $orders = $orders->paginate($filter['perPage'] ?? 10);

        return [
            'total_tax'				=> $tax,
            'total_coupon'			=> $coupon,
            'total_point_history'	=> $pointHistory,
            'total_commission_fee'	=> $commissionFee,
            'total_delivery_fee'	=> $deliveryFee,
            'total_service_fee'		=> $serviceFee,
            'total_price'			=> $totalPrice,
            'total_seller_fee' 		=> $totalPrice - $deliveryFee - $serviceFee - $commissionFee - $coupon - $pointHistory,
            'data' 					=> OrderResource::collection($orders),
            'meta'					=> [
                'page'		=> $orders->currentPage(),
                'perPage'	=> $orders->perPage(),
                'total'		=> $orders->total(),
            ]
        ];
    }

    /**
     * @param array $filter
     * @return array|Collection
     */
    public function ordersReportChartPaginate(array $filter): array|Collection
    {
        $dateFrom = date('Y-m-d 00:00:01', strtotime(data_get($filter, 'date_from')));
        $dateTo   = date('Y-m-d 23:59:59', strtotime(data_get($filter, 'date_to', now()->toString())));
        $locale   = Language::where('default', 1)->first(['locale', 'default'])?->locale;
        $shopId   = data_get($filter, 'shop_id');

        $key      = $filter['column'] ?? 'id';
        $column   = data_get([
            'id',
            'total_price'
        ], $key, $key);

        $orders = Order::with([
            'user:id,firstname,lastname,active',
            'galleries',
            'orderDetails' => fn($q) => $q->with([
                'stock:id,product_id',
                'stock.product:id',
                'stock.product.translation' => fn($q) => $q
                    ->select('id', 'product_id', 'locale', 'title')
                    ->when($this->language, function ($q) use ($locale) {
                        $q->where(fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale));
                    }),
                'stock.stockExtras.value',
                'stock.stockExtras.group.translation' => fn($q) => $q
                    ->when($this->language, function ($q) use ($locale) {
                        $q->where(fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale));
                    }),
            ])
        ])
            ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
            ->where('status', Order::STATUS_DELIVERED)
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo);

        if (data_get($filter, 'export') === 'excel') {

            $name = 'orders-report-products-' . Str::random(8);

            try {
                Excel::store(
                    new OrdersReportExport($orders->get()),
                    "export/$name.xlsx",
                    'public',
                    \Maatwebsite\Excel\Excel::XLSX
                );

                return [
                    'status'    => true,
                    'code'      => ResponseError::NO_ERROR,
                    'path'      => 'public/export',
                    'file_name' => "export/$name.xlsx",
                    'link'      => URL::to("storage/export/$name.xlsx"),
                ];
            } catch (Throwable $e) {
                $this->error($e);
                return [
                    'status'  => false,
                    'code'    => ResponseError::ERROR_501,
                    'message' => $e->getMessage(),
                ];
            }
        }

        $orders = $orders->paginate($filter['perPage'] ?? 10);

        foreach ($orders as $i => $order) {

            /** @var Order $order */

            $result = [
                'id'        => $order->id,
                'status'    => $order->status,
                'firstname' => $order->user?->firstname,
                'lastname'  => $order->user?->lastname,
                'active'    => $order->user?->active,
                'quantity'  => 0,
                'price'     => $order->total_price,
                'products'  => []
            ];

            foreach ($order->orderDetails as $orderDetail) {

                $title = $orderDetail->stock?->product?->translation?->title;

                if (empty($title)) {
                    continue;
                }

                $result['products'][] = $title;
                $result['quantity'] += (int)($orderDetail?->quantity ?? 0);

            }

            data_set($orders, $i, $result);
        }

        $isDesc = ($filter['sort'] ?? 'desc') === 'desc';

        return collect($orders)->sortBy($column, $isDesc ? SORT_DESC : SORT_ASC, $isDesc);
    }

    /**
     * @param array $filter
     * @return array
     */
    public function revenueReport(array $filter): array
    {
        $type = data_get($filter, 'type');

        $type = match ($type) {
            'year'  => 'Y',
            'week'  => 'w',
            'month' => 'Y-m',
            default => 'Y-m-d',
        };

        $dateFrom = date('Y-m-d 00:00:01', strtotime(data_get($filter, 'date_from')));
        $dateTo   = date('Y-m-d 23:59:59', strtotime(data_get($filter, 'date_to', now()->toString())));

        $column   = [
            'count',
            'tax',
            'delivery_fee',
            'canceled_sum',
            'delivered_sum',
            'total_price',
            'total_quantity',
            'delivered_avg',
            'time',
        ];

        if (!in_array(data_get($filter, 'column'), $column)) {
            $filter['column'] = 'id';
        }

        $orders = Order::withSum([
                'orderDetails' => fn($q) => $q->select('id', 'order_id', 'quantity')
            ], 'quantity')
            ->where('created_at', '>=', $dateFrom)
            ->where('created_at', '<=', $dateTo)
            ->whereIn('status', [Order::STATUS_DELIVERED, Order::STATUS_CANCELED])
            ->when(data_get($filter, 'shop_id'), fn($q, $shopId) => $q->where('o.shop_id', $shopId))
            ->select([
                'created_at',
                'id',
                'total_price',
                'status',
                'created_at',
            ])
            ->get();

        $result = [];

        foreach ($orders as $order) {

            /** @var Order $order */

            $isDelivered = $order->status === Order::STATUS_DELIVERED;
            $isCanceled  = $order->status === Order::STATUS_CANCELED;
            $date = date($type, $order->created_at?->unix());

            $canceledPrice      = data_get($result, "$date.canceled_sum", 0);
            $deliveredCount     = data_get($result, "$date.total_quantity", 0);
            $deliveredPrice     = data_get($result, "$date.total_price", 0);
            $deliveredQuantity  = data_get($result, "$date.count", 0);
            $deliveredTax       = data_get($result, "$date.tax", 0);
            $deliveredFee       = data_get($result, "$date.delivery_fee", 0);

            $quantity           = $order->orderDetails->sum('quantity');

            $result[$date] = [
                'time'           => $date,
                'canceled_sum'   => $isCanceled  ? $canceledPrice     + $order->total_price   : $canceledPrice,
                'total_quantity' => $isDelivered ? $deliveredCount    + 1                     : $deliveredCount,
                'total_price'    => $isDelivered ? $deliveredPrice    + $order->total_price   : $deliveredPrice,
                'count'          => $isDelivered ? $deliveredQuantity + $quantity             : $deliveredQuantity,
                'tax'            => $isDelivered ? $deliveredTax      + $order->total_tax     : $deliveredTax,
                'delivery_fee'   => $isDelivered ? $deliveredFee      + $order->delivery_fee  : $deliveredFee,
            ];

        }

        if (data_get($filter, 'export') === 'excel') {

            $name = 'report-revenue-' . Str::random(8);

            try {
                Excel::store(new OrdersRevenueReportExport($result), "export/$name.xlsx",'public');

                return [
                    'status'    => true,
                    'code'      => ResponseError::NO_ERROR,
                    'path'      => 'public/export',
                    'file_name' => "export/$name.xlsx",
                    'link'      => URL::to("storage/export/$name.xlsx"),
                ];
            } catch (Throwable $e) {
                $this->error($e);
                return [
                    'status'  => false,
                    'code'    => ResponseError::ERROR_501,
                    'message' => $e->getMessage(),
                ];
            }

        }

        $result = collect($result);

        return [
            'paginate' => $result->values(),
        ];
    }

    /**
     * @param array $filter
     * @return array
     */
    public function overviewCarts(array $filter): array
    {
        $dateFrom = date('Y-m-d 00:00:01', strtotime(data_get($filter, 'date_from')));
        $dateTo   = date('Y-m-d 23:59:59', strtotime(data_get($filter, 'date_to', now()->toString())));

        $type     = data_get($filter, 'type');
        $shopId   = data_get($filter, 'shop_id');

        $column = [
            'count',
            'tax',
            'delivery_fee',
            'canceled_sum',
            'delivered_sum',
            'delivered_avg',
            'time',
        ];

        if (!in_array(data_get($filter, 'column'), $column)) {
            $filter['column'] = 'time';
        }

        $chart = DB::table('orders')
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
            ->whereIn('status', [Order::STATUS_DELIVERED, Order::STATUS_CANCELED])
            ->select([
                DB::raw("sum(if(status = 'delivered', 1, 0)) as count"),
                DB::raw("sum(if(status = 'delivered', total_tax, 0)) as tax"),
                DB::raw("sum(if(status = 'delivered', delivery_fee, 0)) as delivery_fee"),
                DB::raw("sum(if(status = 'canceled',  total_price, 0)) as canceled_sum"),
                DB::raw("sum(if(status = 'delivered', total_price, 0)) as delivered_sum"),
                DB::raw("avg(if(status = 'delivered', total_price, 0)) as delivered_avg"),
                DB::raw('(DATE_FORMAT(created_at, ' . ($type == 'year' ? "'%Y" : ($type == 'month' ? "'%Y-%m" : "'%Y-%m-%d")) . "')) as time"),
            ])
            ->groupBy('time')
            ->orderBy($filter['column'] ?? 'id', $filter['sort'] ?? 'desc')
            ->get();

        return [
            'chart_price'   => ChartRepository::chart($chart, 'delivered_sum'),
            'chart_count'   => ChartRepository::chart($chart, 'count'),
            'count'         => $chart->sum('count'),
            'tax'           => $chart->sum('tax'),
            'delivery_fee'  => $chart->sum('delivery_fee'),
            'canceled_sum'  => $chart->sum('canceled_sum'),
            'delivered_sum' => $chart->sum('delivered_sum'),
            'delivered_avg' => $chart->sum('delivered_avg'),
        ];

    }

    /**
     * @param array $filter
     * @return array
     */
    public function overviewProducts(array $filter): array
    {
        [$dateFrom, $dateTo, $locale, $key, $shopId] = $this->getKeys($filter);

        $column = data_get(['id', 'count', 'total_price', 'quantity'], $key, $key);

        if ($column == 'id') {
            $column = 'id';
        }

        $orderDetails = OrderDetail::whereHas('order', function ($query) use ($shopId) {
                $query
                    ->whereIn('status', [Order::STATUS_DELIVERED, Order::STATUS_CANCELED])
                    ->when($shopId, fn($q) => $q->where('shop_id', $shopId));
            })
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->select([
                DB::raw('sum(quantity) as quantity'),
                DB::raw('sum(total_price) as total_price'),
                DB::raw('count(id) as count'),
                DB::raw('stock_id as id'),
            ])
            ->groupBy(['id'])
            ->having('count', '>', 0)
            ->orHaving('total_price', '>', 0)
            ->orHaving('quantity', '>', 0)
            ->orderBy($column, $filter['sort'] ?? 'desc')
            ->paginate($filter['perPage'] ?? 10);

        $result = collect($orderDetails->items())->transform(function ($item) use ($locale) {

            /** @var Stock $stock */
            $stock = Stock::with([
                'stockExtras.value',
                'product' => fn($q) => $q->select('id'),
                'product.translation' => fn($q) => $q
                    ->select('id', 'product_id', 'locale', 'title')
                    ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                        $q->where('locale', $this->language)->orWhere('locale', $locale);
                    })),
            ])
                ->select(['product_id', 'id'])
                ->find($item->id);

            $extras  = collect($stock?->stockExtras)
                ->pluck('value.value')
                ->toArray();

            $extras = implode(', ', $extras);

            $item->title = "{$stock?->product?->translation?->title}, $extras";

            return $item;
        });

        return [
            'data' => $result,
            'meta' => [
                'last_page'  => $orderDetails->lastPage(),
                'page'       => $orderDetails->currentPage(),
                'total'      => $orderDetails->total(),
                'more_pages' => $orderDetails->hasMorePages(),
                'has_pages'  => $orderDetails->hasPages(),
            ]
        ];
    }

    /**
     * @param array $filter
     * @return array
     */
    public function overviewCategories(array $filter): array
    {
        [$dateFrom, $dateTo, $locale, $key, $shopId] = $this->getKeys($filter);

        $column = data_get(['id', 'count', 'total_price', 'quantity'], $key, $key);

        if ($column == 'id') {
            $column = 'id';
        }

        $orderDetails = DB::table('products as p')
            ->crossJoin('stocks as s', 'p.id', '=', 's.product_id')
            ->crossJoin('order_details as od', 's.id', '=', 'od.stock_id')
            ->crossJoin('orders as o', function ($builder) use ($dateFrom, $dateTo) {
                $builder
                    ->on('od.order_id', '=', 'o.id')
                    ->whereDate('o.created_at', '>=', $dateFrom)
                    ->whereDate('o.created_at', '<=', $dateTo)
                    ->whereIn('o.status', [Order::STATUS_DELIVERED, Order::STATUS_CANCELED]);
            })
            ->when($shopId, fn($q) => $q->where('p.shop_id', '=', $shopId))
            ->select([
                DB::raw('sum(distinct od.quantity) as quantity'),
                DB::raw('sum(distinct o.total_price) as total_price'),
                DB::raw('count(distinct o.id) as count'),
                DB::raw('p.category_id as id'),
            ])
            ->groupBy(['id'])
            ->having('count', '>', '0')
            ->orHaving('total_price', '>', '0')
            ->orHaving('quantity', '>', '0')
            ->orderBy($column, $filter['sort'] ?? 'desc')
            ->paginate($filter['perPage'] ?? 10);

        $result = collect($orderDetails->items())->transform(function ($item) use ($locale) {

            $translation = CategoryTranslation::where('category_id', data_get($item, 'id'))
                ->when($this->language, function ($q) use ($locale) {
                    $q->where(fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale));
                })
                ->select('title')
                ->first();

            $item->title = data_get($translation, 'title', 'EMPTY');

            return $item;
        });

        return [
            'data' => $result,
            'meta' => [
                'last_page'  => $orderDetails->lastPage(),
                'page'       => $orderDetails->currentPage(),
                'total'      => $orderDetails->total(),
                'more_pages' => $orderDetails->hasMorePages(),
                'has_pages'  => $orderDetails->hasPages(),
            ]
        ];
    }

    public function getKeys(array $filter): array
    {
        $dateFrom   = date('Y-m-d 00:00:01', strtotime(data_get($filter, 'date_from')));
        $dateTo     = date('Y-m-d 23:59:59', strtotime(data_get($filter, 'date_to', now()->toString())));
        $locale     = Language::where('default', 1)->first()?->locale;
        $key        = data_get($filter, 'column', 'count');
        $shopId     = data_get($filter, 'shop_id');

        return [$dateFrom, $dateTo, $locale, $key, $shopId];
    }
}
