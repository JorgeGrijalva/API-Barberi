<?php
declare(strict_types=1);

namespace App\Services\OrderService;

use App\Helpers\OrderHelper;
use App\Helpers\ResponseError;
use App\Jobs\PayReferral;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\OrderRefund;
use App\Models\PaymentToPartner;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserDigitalFile;
use App\Models\WalletHistory;
use App\Services\CoreService;
use App\Services\WalletHistoryService\WalletHistoryService;
use DB;
use Exception;
use Throwable;

class OrderRefundService extends CoreService
{
    protected function getModelClass(): string
    {
        return OrderRefund::class;
    }

    /**
     * @param array $data
     * @return array
     */
    public function create(array $data): array
    {
        try {
            $exist = OrderRefund::where('order_id', data_get($data,'order_id'))->first();

            if (in_array(data_get($exist, 'status'), [OrderRefund::STATUS_PENDING, OrderRefund::STATUS_ACCEPTED])) {
                return [
                    'status'    => false,
                    'code'      => ResponseError::ERROR_506,
                    'message'   => __('errors.' . ResponseError::ERROR_506, locale: $this->language),
                ];
            }

            $this->checkDigital((int)data_get($data,'order_id'));

            /** @var OrderRefund $orderRefund */
            $orderRefund = $this->model();

            $orderRefund->create($data);

            if (data_get($data, 'images.0')) {
                $orderRefund->uploads(data_get($data, 'images'));
            }

            return ['status' => true, 'message' => ResponseError::NO_ERROR];

        } catch (Throwable $e) {
            $this->error($e);

            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_501,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * @param int $orderId
     * @return void
     * @throws Exception
     */
    public function checkDigital(int $orderId): void
    {
        /** @var Order $order */
        $order = Order::with([
            'orderDetails:id,order_id,stock_id',
            'orderDetails.stock:id,product_id',
            'orderDetails.stock.product:id',
            'orderDetails.stock.product.digitalFile:id,product_id',
        ])
            ->select(['id'])
            ->find($orderId);

        $digital = 0;
        $product = 0;

        foreach ($order->orderDetails as $orderDetail) {

            $digitalFile = UserDigitalFile::where([
                'digital_file_id' => $orderDetail->stock?->product?->digitalFile?->id,
                'user_id'         => $order->user_id,
            ])
                ->first();

            if (!empty($digitalFile)) {
                $digital += 1;
                continue;
            }

            $product += 1;

        }

        if ($digital > 0 && $product === 0) {
            throw new Exception('can not refund digital order');
        }

    }

    public function update(OrderRefund $orderRefund, array $data): array
    {
        try {

            if ($orderRefund->status == data_get($data, 'status')) {
                return [
                    'status'  => false,
                    'code'    => ResponseError::ERROR_252,
                    'message' => __('errors.' . ResponseError::ERROR_252, locale: $this->language)
                ];
            }

            $orderRefund = $orderRefund->loadMissing([
                'order.shop:id,uuid,user_id',
                'order.shop.seller:id',
                'order.shop.seller.wallet:id,user_id,uuid',
                'order.deliveryman:id',
                'order.deliveryman.wallet:id,user_id,uuid',
                'order.user:id',
                'order.user.wallet:id,user_id,uuid',
                'order.transactions',
            ]);

            /** @var User $user */
            $user = data_get($orderRefund->order, 'user');

            /** @var Transaction $transaction */
            $transaction = $orderRefund?->order
                ?->transactions()
                ?->where('status', Transaction::STATUS_PAID)
                ?->first();

            if (data_get($data, 'status') === OrderRefund::STATUS_ACCEPTED) {

                if (!$user->wallet) {
                    return [
                        'status'  => false,
                        'message' => __('errors.' . ResponseError::ERROR_108, locale: $this->language),
                        'code'    => ResponseError::ERROR_108
                    ];
                }

                if (!$orderRefund->order) {
                    return [
                        'status'  => false,
                        'message' => __('errors' . ResponseError::ORDER_NOT_FOUND, locale: $this->language),
                        'code'    => ResponseError::ERROR_404
                    ];
                }

                /** @var Transaction $existRefund */
                $existRefund = $orderRefund->order->transactions()
                    ->where('status', Transaction::STATUS_REFUND)
                    ->first();

                if ($existRefund) {
                    return [
                        'status'  => false,
                        'code'    => ResponseError::ERROR_501,
                        'message' => __('errors.' . ResponseError::ORDER_REFUNDED, locale: $this->language),
                    ];
                }

            }

            DB::transaction(function () use ($orderRefund, $data, $user, $transaction) {

                $orderRefund->update($data);

                if (data_get($data, 'images.0')) {
                    $orderRefund->galleries()->delete();
                    $orderRefund->uploads(data_get($data, 'images'));
                }

                if ($orderRefund->status !== OrderRefund::STATUS_ACCEPTED) {
                    return true;
                }

                if (!$transaction?->id) {
                    return true;
                }

                $order = $orderRefund->order;

                if (!$order->transactions->where('status', Transaction::STATUS_PAID)->first()?->id) {
                    return true;
                }

                if ($order->status === Order::STATUS_DELIVERED) {

                    PayReferral::dispatchAfterResponse($order->user, 'decrement');

                    if (!$order->shop?->seller?->wallet?->id) {
                        throw new Exception(__('errors.' . ResponseError::ERROR_114, locale: $this->language));
                    }

                    $sellerPrice = $order->total_price - $order->commission_fee;

                    /** @var PaymentToPartner $sellerPartner */
                    $sellerPartner = PaymentToPartner::with([
                        'transaction'
                    ])
                        ->where([
                            'user_id'   => $order->shop->seller->id,
                            'order_id'  => $order->id,
                            'type'	    => PaymentToPartner::SELLER,
                        ])
                        ->first();

                    if ($sellerPartner?->transaction?->status === Transaction::STATUS_PAID) {
                        (new WalletHistoryService)->create([
                            'type'   => 'withdraw',
                            'price'  => $sellerPrice,
                            'note'   => "For Order #$order->id",
                            'status' => WalletHistory::PAID,
                            'user'   => $order->shop->seller
                        ]);
                    }

                    if ($order->delivery_type == Order::DELIVERY && $order->deliveryman?->wallet?->id) {

                        /** @var PaymentToPartner $deliveryManPartner */
                        $deliveryManPartner = PaymentToPartner::with([
                            'transaction'
                        ])
                            ->where([
                                'user_id'   => $order->deliveryman_id,
                                'order_id'  => $order->id,
                                'type'      => PaymentToPartner::DELIVERYMAN,
                            ])
                            ->first();

                        if ($deliveryManPartner?->transaction?->status === Transaction::STATUS_PAID) {
                            (new WalletHistoryService)->create([
                                'type'   => 'withdraw',
                                'price'  => $order->delivery_fee,
                                'note'   => "For Order #$order->id",
                                'status' => WalletHistory::PAID,
                                'user'   => $order->deliveryman
                            ]);
                        }

                    }

                }

                $totalPrice = $this->refundProduct($order, $order->total_price);

                (new WalletHistoryService)->create([
                    'type'   => 'topup',
                    'price'  => $totalPrice,
                    'note'   => "For Order #$order->id",
                    'status' => WalletHistory::PAID,
                    'user'   => $user
                ]);

                return true;
            });

            return ['status' => true, 'message' => ResponseError::NO_ERROR];

        } catch (Throwable $e) {
            $this->error($e);

            return ['status' => false, 'code' => ResponseError::ERROR_501, 'message' => $e->getMessage()];
        }
    }

    public function delete(?array $ids = [], ?int $shopId = null, ?bool $isAdmin = false): array
    {
        try {

           foreach (OrderRefund::find(is_array($ids) ? $ids : []) as $orderRefund) {

               if (!$isAdmin) {
                   if (empty($shopId) && data_get($orderRefund->order, 'user_id') !== auth('sanctum')->id()) {
                       continue;
                   } else if (!in_array($orderRefund->status, [OrderRefund::STATUS_ACCEPTED, OrderRefund::STATUS_CANCELED])) {
                       continue;
                   }
               }

               if (!empty($shopId) && $orderRefund->order?->shop_id !== $shopId) {
                   continue;
               }

               $orderRefund->galleries()->delete();
               $orderRefund->delete();

           }

            return [
                'status'  => true,
                'message' => ResponseError::NO_ERROR,
            ];

        } catch (Throwable $e) {
            $this->error($e);

            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_503,
                'message' => __('errors.' . ResponseError::ERROR_503, locale: $this->language),
            ];
        }
    }


    public function refundProduct(Order $order, int|float|null $totalPrice): int|float|null
    {

        $order->orderDetails->map(function (OrderDetail $orderDetail) use ($order, &$totalPrice) {

            $digitalFile = UserDigitalFile::where([
                'digital_file_id' => $orderDetail->stock?->product?->digitalFile?->id,
                'user_id'         => $order->user_id,
                'downloaded'      => true,
            ])
                ->first();

            if (!empty($digitalFile)) {
                $totalPrice -= $orderDetail->total_price;
            }

            OrderHelper::updateStatCount($orderDetail->stock, $orderDetail->quantity, false);

        });

        return $totalPrice;
    }
}
