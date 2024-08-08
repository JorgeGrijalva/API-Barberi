<?php
declare(strict_types=1);

namespace App\Services\OrderService;

use App\Helpers\NotificationHelper;
use App\Helpers\OrderHelper;
use App\Helpers\ResponseError;
use App\Models\Language;
use App\Models\PushNotification;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Settings;
use App\Models\User;
use App\Services\CoreService;
use App\Services\TransactionService\TransactionService;
use App\Traits\Notification;
use DB;
use Exception;
use Throwable;

class OrderService extends CoreService
{
    use Notification;

    protected function getModelClass(): string
    {
        return Order::class;
    }

    private function with(): array
    {
        $locale = Language::where('default', 1)->first()?->locale;

        return [
            'user',
            'review',
            'pointHistories',
            'currency',
            'deliveryman',
            'coupon',
            'shop:id,latitude,longitude,tax,background_img,logo_img,uuid,phone,user_id,verify',
            'shop.translation' => fn($q) => $q
                ->select([
                    'id',
                    'shop_id',
                    'locale',
                    'title',
                    'address',
                ])
                ->where('locale', $this->language)
                ->orWhere('locale', $locale),

            'orderDetails.stock.discount' => fn($q) => $q->where('start', '<=', today())
                ->where('end', '>=', today())
                ->where('active', 1),

            'orderDetails.stock.product.translation' => fn($q) => $q
                ->select([
                    'id',
                    'product_id',
                    'locale',
                    'title',
                ])
                ->where('locale', $this->language)
                ->orWhere('locale', $locale),

            'orderDetails.stock.stockExtras.value',
            'orderDetails.stock.stockExtras.group.translation' => function ($q) use ($locale) {
                $q->select('id', 'extra_group_id', 'locale', 'title')
                    ->where('locale', $this->language)
                    ->orWhere('locale', $locale);
            },

            'orderDetails.replaceStock.discount' => fn($q) => $q->where('start', '<=', today())
                ->where('end', '>=', today())
                ->where('active', 1),

            'orderDetails.replaceStock.product.translation' => fn($q) => $q
                ->select([
                    'id',
                    'product_id',
                    'locale',
                    'title',
                ])
                ->where('locale', $this->language)
                ->orWhere('locale', $locale),

            'orderDetails.replaceStock.stockExtras.value',
            'orderDetails.replaceStock.stockExtras.group.translation' => function ($q) use ($locale) {
                $q->select('id', 'extra_group_id', 'locale', 'title')
                    ->where('locale', $this->language)
                    ->orWhere('locale', $locale);
            },
            'orderRefunds',
            'transaction.paymentSystem',
            'galleries',
            'myAddress',
        ];
    }

    /**
     * @param array $data
     * @return array
     */
    public function create(array $data): array
    {
        try {
            OrderHelper::checkPhoneIfRequired($data, $this->language);

            $orders = DB::transaction(function () use ($data) {

                $orders = match (true) {
                    isset($data['data'])    => (new POSOrderService)->create($data),
                    isset($data['cart_id']) => (new CartOrderService)->create($data, $data['notes'] ?? []),
                    default => throw new Exception('error data'),
                };

                foreach ($orders as $key => $order) {

                    $this->calculateOrder($order, $data, false, count($orders));

                    $orders[$key] = $order->fresh($this->with());

                }

                return $orders;
            });

            return [
                'status'  => true,
                'message' => ResponseError::NO_ERROR,
                'data'    => $orders
            ];

        } catch (Throwable $e) {
            $this->error($e);

            return [
                'status'  => false,
                'message' => $e->getMessage(),
                'code'    => ResponseError::ERROR_501,
            ];
        }
    }

    /**
     * @param int $id
     * @param array $data
     * @return array
     */
    public function update(int $id, array $data): array
    {
        try {
//            OrderHelper::checkPhoneIfRequired($data, $this->language);

            /** @var Order $order */
            $order = DB::transaction(function () use ($data, $id) {

                /** @var Order $order */
                $order = $this->model()
                    ->with([
                        'orderDetails',
                        'transaction'
                    ])
                    ->find($id);

                if (!$order) {
                    throw new Exception(__('errors.' . ResponseError::ORDER_NOT_FOUND, locale: $this->language));
                }

                $order->update($data);

                if (data_get($data, 'images.0')) {

                    $order->galleries()->delete();
                    $order->update(['img' => data_get($data, 'images.0')]);
                    $order->uploads(data_get($data, 'images'));

                }

                $order = (new OrderDetailService)->update($order, data_get($data, 'products', []));

                $this->calculateOrder($order, $data, true);

                return $order;
            });

            return [
                'status'  => true,
                'message' => ResponseError::NO_ERROR,
                'data'    => $order->fresh($this->with())
            ];

        } catch (Throwable $e) {
            $this->error($e);
            return [
                'status'  => false,
                'message' => $e->getMessage() . $e->getFile() . $e->getLine(),
                'code'    => ResponseError::ERROR_502
            ];
        }
    }

    /**
     * @param Order $order
     * @param array $data
     * @param bool $isUpdate
     * @param int $ordersCount
     * @return void
     * @throws Exception
     */
    private function calculateOrder(Order $order, array $data, bool $isUpdate = false, int $ordersCount = 0): void
    {
        $locale = Language::where('default', 1)->first()?->locale;

        /** @var Order $order */
        $order = $order->fresh([
            'shop:id,tax,percentage,visibility',
            'shop.translation'   => fn($q) => $q
                ->when($this->language, function ($q) use ($locale) {
                    $q->where(fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale));
                }),
            'shop.subscription.subscription',
        ]);

        $isSubscribe = (int)Settings::where('key', 'by_subscription')->first()?->value;

        $totalPrice = $order->orderDetails->sum('total_price');
        $discount   = $order->orderDetails->sum('discount');

        $shopTax = max($totalPrice / 100 * $order->shop?->tax, 0);

        $deliveryFee = [];

        OrderHelper::checkShopDelivery($order->shop, $data, $this->language, $deliveryFee);

        $couponPrice = collect();
        $couponPrice = OrderHelper::checkCoupon($data, $order->shop_id, $totalPrice, $order->rate, $couponPrice, $deliveryFee);

        foreach ($couponPrice as $coupon) {
            $this->createOrderCoupon($coupon['coupon'], $order, $totalPrice);
        }

        $totalPrice += $shopTax;

        $percent = $order->shop?->percentage;

        $commissionFee = !$isSubscribe ? max(($totalPrice / 100 * ($percent <= 0.99 ? 1 : $percent)), 0) : 0;

        if ($isSubscribe) {

            $orderLimit = $order->shop?->subscription?->subscription?->order_limit;

            $shopOrdersCount = DB::table('orders')
                ->select(['shop_id'])
                ->where('shop_id', $order->shop_id)
                ->count('shop_id');

            if ($orderLimit < $shopOrdersCount) {
                $order->shop?->update([
                    'visibility' => 0
                ]);
            }

        }

        $serviceFee = (double)Settings::where('key', 'service_fee')->first()?->value ?: 0;

        $serviceFee = !$isUpdate
            ? $serviceFee > 0 ? $serviceFee / $ordersCount : $serviceFee
            : $order->service_fee;

        $couponPriceSum = collect($couponPrice)->sum('price');

        $deliveryFeeSum = collect($deliveryFee)->sum('price');

        $totalPrice += $deliveryFeeSum;
        $totalPrice -= $couponPriceSum;
        $totalPrice += $serviceFee;

        $order->update([
            'total_price'    => $totalPrice,
            'commission_fee' => $commissionFee,
            'total_discount' => max($discount, 0),
            'total_tax'      => $shopTax,
            'delivery_fee'   => $deliveryFeeSum === 0 ? $order->delivery_fee : $deliveryFeeSum,
            'coupon_price'   => $couponPriceSum === 0 ? $order->coupon_price : $couponPriceSum,
            'service_fee'    => $serviceFee,
        ]);

        if (data_get($data, 'payment_id') && !data_get($data, 'trx_status')) {

            $data['payment_sys_id'] = data_get($data, 'payment_id');

            $result = (new TransactionService)->orderTransaction($order->id, $data);

            if (!data_get($result, 'status')) {
                throw new Exception(data_get($result, 'message'));
            }

        }

        OrderHelper::updateUserOrderStat($order);
    }

    /**
     * @param Coupon $coupon
     * @param Order $order
     * @param $totalPrice
     * @return float|int|null
     */
    private function createOrderCoupon(Coupon $coupon, Order $order, $totalPrice): float|int|null
    {
        if ($coupon->qty <= 0) {
            return 0;
        }

        $couponPrice = $coupon->type === 'percent' ? ($totalPrice / 100) * $coupon->price : $coupon->price;

        $order->coupon()->updateOrCreate([
            'user_id' => $order->user_id,
            'name'    => $coupon->name,
        ], [
            'price'   => $couponPrice,
        ]);

        $coupon->decrement('qty');

        return $couponPrice;
    }

    /**
     * @param int|null $orderId
     * @param int $deliveryman
     * @return array
     */
    public function updateDeliveryMan(?int $orderId, int $deliveryman): array
    {
        try {
            /** @var Order $order */
            $order = Order::find($orderId);

            if (!$order) {
                return [
                    'status'  => false,
                    'code'    => ResponseError::ERROR_404,
                    'message' => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
                ];
            }

            if ($order->delivery_type != Order::DELIVERY) {
                return [
                    'status'  => false,
                    'code'    => ResponseError::ERROR_502,
                    'message' => __('errors.' . ResponseError::ORDER_POINT, locale: $this->language)
                ];
            }

            /** @var User $user */
            $user = User::with('deliveryManSetting')->find($deliveryman);

            if (!$user || !$user->hasRole('deliveryman')) {
                return [
                    'status'  => false,
                    'code'    => ResponseError::ERROR_211,
                    'message' => __('errors.' . ResponseError::ERROR_211, locale: $this->language)
                ];
            }

            $order->update([
                'deliveryman_id' => $user->id,
            ]);

            $this->sendNotification(
                $order,
                is_array($user->firebase_token) ? $user->firebase_token : [$user->firebase_token],
                __('errors.' . ResponseError::NEW_ORDER, ['id' => $order->id], $user->lang ?? $this->language),
                $order->id,
                (new NotificationHelper)->deliveryManOrder($order, PushNotification::NEW_ORDER),
                [$user->id]
            );

            return [
                'status'  => true,
                'message' => ResponseError::NO_ERROR,
                'data'    => $order,
                'user'    => $user
            ];
        } catch (Throwable $e) {
            $this->error($e);
            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_501,
                'message' => __('errors.' . ResponseError::ERROR_501, locale: $this->language)
            ];
        }
    }

    /**
     * @param int|null $id
     * @return array
     */
    public function attachDeliveryMan(?int $id): array
    {
        try {
            /** @var Order $order */
            $order = Order::find($id);

            if ($order->delivery_type != Order::DELIVERY) {
                return [
                    'status'  => false,
                    'code'    => ResponseError::ERROR_502,
                    'message' => __('errors.' . ResponseError::ORDER_POINT, locale: $this->language)
                ];
            }

            if (!empty($order->deliveryman)) {
                return [
                    'status'  => false,
                    'code'    => ResponseError::ERROR_210,
                    'message' => __('errors.' . ResponseError::ERROR_210, locale: $this->language)
                ];
            }

            $order->update([
                'deliveryman_id' => auth('sanctum')->id(),
            ]);

            return ['status' => true, 'message' => ResponseError::NO_ERROR, 'data' => $order];
        } catch (Throwable) {
            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_502,
                'message' => __('errors.' . ResponseError::ERROR_502, locale: $this->language)
            ];
        }
    }

    /**
     * @param array|null $ids
     * @param int|null $shopId
     * @return array
     */
    public function destroy(?array $ids = [], ?int $shopId = null): array
    {
        $errors = [];

        $orders = Order::with([
            'coupon',
            'orderDetails.stock.product'
        ])
            ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
            ->find(is_array($ids) ? $ids : []);

        foreach ($orders as $order) {

            try {
                DB::transaction(function () use ($order) {

                    /** @var Order $order */
                    foreach ($order->orderDetails as $orderDetail) {

                        OrderHelper::updateStatCount(
                            $orderDetail->stock,
                            $orderDetail?->quantity,
                            false
                        );

                        $orderDetail->delete();
                    }

                    DB::table('push_notifications')
                        ->where('model_type', Order::class)
                        ->where('model_id', $order->id)
                        ->delete();

                    $order->user->update([
                        'o_count' => $order->user->o_count - 1,
                        'o_sum'   => $order->user->o_sum - $order->total_price,
                    ]);

                    $order->delete();

                });
            } catch (Throwable $e) {
                $errors[] = $order->id;

                $this->error($e);
            }

        }

        return $errors;
    }

    /**
     * @param int $id
     * @param int|null $userId
     * @return array
     */
    public function setCurrent(int $id, ?int $userId = null): array
    {
        $errors = [];

        $orders = Order::when($userId, fn($q) => $q->where('deliveryman_id', $userId))
            ->where('current', 1)

            ->orWhere('id', $id)
            ->get();

        $getOrder = new Order;

        foreach ($orders as $order) {

            try {

                if ($order->id === $id) {

                    $order->update([
                        'current' => true,
                    ]);

                    $getOrder = $order;

                    continue;

                }

                $order->update([
                    'current' => false,
                ]);

            } catch (Throwable $e) {
                $errors[] = $order->id;

                $this->error($e);
            }

        }

        return count($errors) === 0 ? [
            'status' => true,
            'code' => ResponseError::NO_ERROR,
            'data' => $getOrder
        ] : [
            'status'  => false,
            'code'    => ResponseError::ERROR_400,
            'message' => __(
                'errors.' . ResponseError::CANT_UPDATE_ORDERS,
                [
                    'ids' => implode(', #', $errors)
                ],
                $this->language
            )
        ];
    }

    /**
     * @param int $orderId
     * @param array $data
     * @return Order
     * @throws Exception
     */
    public function trackingUpdate(int $orderId, array $data): Order
    {
        $order = Order::find($orderId);

        if (!$order) {
            throw new Exception(__('errors.' . ResponseError::ORDER_NOT_FOUND, locale: $this->language));
        }

        $order->update($data);

        return $order;
    }
}
