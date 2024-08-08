<?php
declare(strict_types=1);

namespace App\Services\OrderService;

use App\Helpers\ResponseError;
use App\Jobs\PayReferral;
use App\Models\Language;
use App\Models\NotificationUser;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Point;
use App\Models\PointHistory;
use App\Models\PushNotification;
use App\Models\Shop;
use App\Models\ShopAdsPackage;
use App\Models\Transaction;
use App\Models\Translation;
use App\Models\User;
use App\Models\WalletHistory;
use App\Services\CoreService;
use App\Services\WalletHistoryService\WalletHistoryService;
use App\Traits\Notification;
use App\Traits\PaymentRefund;
use DB;
use Exception;
use Http;
use Log;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;
use Throwable;

class OrderStatusUpdateService extends CoreService
{
    use Notification, PaymentRefund;

    /**
     * @return string
     */
    protected function getModelClass(): string
    {
        return Order::class;
    }

    /**
     * @param Order $order
     * @param array $data
     * @param bool $isDelivery
     * @return array
     */
    public function statusUpdate(Order $order, array $data, bool $isDelivery = false): array
    {
        $status = $data['status'];
        try {
            $this->updateNotes($order, $data);

            if ($order->status !== $status) {
                $order = DB::transaction(function () use ($order, $status) {

                    if ($status == Order::STATUS_DELIVERED) {

                        $this->adminWalletTopUp($order);

                        $point = Point::getActualPoint($order->total_price);

                        if (!empty($point)) {
                            $token  = $order->user?->firebase_token;
                            $token = is_array($token) ? $token : [$token];

                            /** @var NotificationUser $notification */
                            $notification = $order->user
                                ?->notifications
                                ?->where('type', \App\Models\Notification::PUSH)
                                ?->first();

                            if ($notification?->notification?->active) {

                                $title = __(
                                    'errors.' . ResponseError::ADD_CASHBACK,
                                    locale: $order?->user?->lang ?? $this->language
                                );

                                $this->sendNotification(
                                    $order,
                                    $token,
                                    $title,
                                    $title,
                                    [
                                        'id'     => $order->id,
                                        'status' => $order->status,
                                        'type'   => PushNotification::ADD_CASHBACK
                                    ],
                                    [$order->user_id]
                                );
                            }

                            $order->pointHistories()->create([
                                'user_id' => $order->user_id,
                                'price'   => $point,
                                'note'    => 'cashback',
                            ]);

                            $order->user?->wallet?->increment('price', $point);
                        }

                        if ($order?->transaction?->paymentSystem?->tag === 'cash') {
                            $order->transaction?->update([
                                'status' => Transaction::STATUS_PAID
                            ]);
                        }

                        PayReferral::dispatchAfterResponse($order->user, 'increment');
                    }

                    $totalPrice = $order->total_price;

                    if ($status == Order::STATUS_CANCELED && $order->orderRefunds?->count() === 0) {
                        $this->refund($order);
                    }

                    $order->update([
                        'status'         => $status,
                        'canceled_note'  => request()->input('canceled_note'),
                        'total_price'    => $totalPrice,
                        'current'        => in_array($status, [Order::STATUS_DELIVERED, Order::STATUS_CANCELED]) ? 0 : $order->current,
                    ]);

                    return $order;
                });
            }
        } catch (Throwable $e) {

            $this->error($e);

            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_501,
                'message' => $e->getMessage()
            ];
        }

        $default = Language::where('default', 1)->first()?->locale;

        /** @var Order $order */
        $this->statusUpdateNotify($order, $isDelivery);

        $seller = $order->shop?->seller;

        $tStatus = Translation::where(function ($q) use ($seller, $default) {
            $q->where('locale', $seller->lang ?? $this->language)->orWhere('locale', $default);
        })
            ->where('key', $status)
            ->first()
            ?->value;

        $replace = [
            'id'     => $order->id,
            'status' => $tStatus ?? $status,
        ];

        if (in_array($status, [Order::STATUS_ON_A_WAY, Order::STATUS_DELIVERED, Order::STATUS_CANCELED]) && $seller) {

            $this->sendNotification(
                $order,
                $seller->firebase_token ?? [],
                __('errors.' . ResponseError::STATUS_CHANGED, $replace, $seller->lang ?? $this->language),
                __('errors.' . ResponseError::STATUS_CHANGED, $replace, $seller->lang ?? $this->language),
                [
                    'id'     => $order->id,
                    'status' => $order->status,
                    'type'   => PushNotification::STATUS_CHANGED
                ],
                [$seller->id]
            );

        } else if (
            in_array($status, [Order::STATUS_ACCEPTED, Order::STATUS_READY])
            && $seller
            && $order->shop->delivery_type === Shop::DELIVERY_TYPE_IN_HOUSE
        ) {

            $this->sendNotification(
                $order,
                $seller->firebase_token ?? [],
                __('errors.' . ResponseError::STATUS_CHANGED, $replace, $seller->lang ?? $this->language),
                __('errors.' . ResponseError::STATUS_CHANGED, $replace, $seller->lang ?? $this->language),
                [
                    'id'     => $order->id,
                    'status' => $order->status,
                    'type'   => PushNotification::STATUS_CHANGED
                ],
                [$seller->id]
            );

        }

        return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $order];
    }

    /**
     * @param Order $order
     * @return void
     * @throws Throwable
     */
    private function adminWalletTopUp(Order $order): void
    {
        /** @var User $admin */
        $admin = User::with('wallet')->whereHas('roles', fn($q) => $q->where('name', 'admin'))->first();

        if (!$admin->wallet) {
            Log::error("admin #$admin?->id doesnt have wallet");
            return;
        }

        $request = request()->merge([
            'type'   => 'topup',
            'price'  => $order->total_price,
            'note'   => "For Seller Order #$order->id",
            'status' => WalletHistory::PAID,
            'user'   => $admin,
        ])->all();

        (new WalletHistoryService)->create($request);
    }

    /**
     * @throws Exception
     */
    public function updateNotes(Order $order, array $data) {
        try {
            $notes = $order->notes?->where('status', $data['status'])?->first()?->notes ?? [];

            if (isset($data['notes'])) {
                foreach ($data['notes'] as $key => $value) {
                    $value['created_at'] = $value['created_at'] ?? now()->format('Y-m-d H:i:s') . 'Z';
                    $notes[$key] = $value;
                }
            }

            $order->notes()->updateOrCreate([
                'status' => $data['status'],
            ], [
                'notes'      => $notes,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        } catch (Throwable $e) {
            throw new Exception($e->getMessage() . $e->getLine() . $e->getFile());
        }
    }

    /**
     * @throws Throwable
     * @throws ApiErrorException
     */
    private function refund($order)
    {
        if (!in_array($order->transaction->paymentSystem->tag, [Payment::TAG_WALLET, Payment::TAG_CASH, Payment::TAG_ZAIN_CASH])) {
            $this->paymentRefund($order);
        }

        if (!in_array($order->transaction->paymentSystem->tag, [Payment::TAG_WALLET, Payment::TAG_CASH])) {
           return;
        }

        $user  = $order->user;
        $trxId = $order->transactions->where('status', Transaction::STATUS_PAID)->first()?->id;

        if (!$user?->wallet && $trxId) {
            throw new Exception(__('errors.' . ResponseError::ERROR_108, locale: $this->language));
        }

        $totalPrice = (new OrderRefundService)->refundProduct($order, $order->total_price);

        if ($trxId) {

            (new WalletHistoryService)->create([
                'type'   => 'topup',
                'price'  => $totalPrice,
                'note'   => "For Order #$order->id",
                'status' => WalletHistory::PAID,
                'user'   => $user
            ]);

        }

        if ($order->pointHistories?->count() > 0) {
            foreach ($order->pointHistories as $pointHistory) {
                /** @var PointHistory $pointHistory */
                $order->user?->wallet?->decrement('price', $pointHistory->price);
                $pointHistory->delete();
            }
        }

        if ($order->status === Order::STATUS_DELIVERED) {
            PayReferral::dispatchAfterResponse($order->user, 'decrement');
        }
    }
}
