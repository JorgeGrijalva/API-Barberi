<?php
declare(strict_types=1);

namespace App\Services\PaymentService;

use App\Helpers\GetShop;
use App\Helpers\ResponseError;
use App\Models\AdsPackage;
use App\Models\Booking;
use App\Models\BookingExtraTime;
use App\Models\Cart;
use App\Models\Currency;
use App\Models\GiftCart;
use App\Models\MemberShip;
use App\Models\Order;
use App\Models\ParcelOrder;
use App\Models\PaymentProcess;
use App\Models\Payout;
use App\Models\Shop;
use App\Models\ShopAdsPackage;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\UserGiftCart;
use App\Models\UserMemberShip;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletHistory;
use App\Repositories\CartRepository\CartRepository;
use App\Services\CoreService;
use App\Services\OrderService\OrderService;
use App\Services\SubscriptionService\SubscriptionService;
use App\Services\TransactionService\TransactionService;
use App\Services\WalletHistoryService\WalletHistoryService;
use App\Traits\Notification;
use Exception;
use Log;
use Throwable;

class BaseService extends CoreService
{
    use Notification;

    protected function getModelClass(): string
    {
        return Payout::class;
    }

    /**
     * @param $token
     * @param $status
     * @param string|null $secondToken
     * @return array
     */
    public function afterHook($token, $status, ?string $secondToken = null): array
    {
        try {
            $paymentProcess = PaymentProcess::with([
                'model',
                'user',
            ])
                ->where('id', $token)
                ->orWhere('id', $secondToken)
                ->first();

            if (empty($paymentProcess)) {
                return [
                    'status'  => false,
                    'message' => 'empty process',
                ];
            }

            /** @var PaymentProcess $paymentProcess */
            if ($paymentProcess->model_type === Subscription::class) {

                $subscription = $paymentProcess->model;

                $shop = Shop::find(data_get($paymentProcess->data, 'shop_id'));

                $shopSubscription = (new SubscriptionService)->subscriptionAttach(
                    $subscription,
                    (int)$shop?->id,
                    $status === 'paid' ? 1 : 0
                );

                $shopSubscription->fresh(['transaction'])?->transaction?->update([
                    'payment_trx_id' => $token,
                    'status'         => $status,
                ]);

                return [
                    'status'  => true,
                    'message' => 'subs success',
                ];
            }

            if ($paymentProcess->model_type === GiftCart::class && $status === Transaction::STATUS_PAID) {

                /** @var GiftCart $model */
                $model = $paymentProcess->model;

                $userGiftCart = UserGiftCart::where([
                    ['gift_cart_id', $paymentProcess->model_id],
                    ['user_id',      $paymentProcess->user_id],
                    ['created_at', '>=', date('Y-m-d H:i:s', strtotime('-10 second'))],
                ])->first();

                if (empty($userGiftCart)) {
                    $userGiftCart = UserGiftCart::create([
                        'gift_cart_id'   => $paymentProcess->model_id,
                        'user_id'        => $paymentProcess->user_id,
                        'price'          => $model->price,
                        'expired_at'     => date('Y-m-d H:i:s', strtotime("+$model->time")),
                    ]);
                }

                $userGiftCart->createTransaction([
                    'price'          => $model->price,
                    'payment_sys_id' => data_get($paymentProcess->data, 'payment_id'),
                    'user_id'        => $paymentProcess->user_id,
                    'payment_trx_id' => $token,
                    'status'         => $status,
                ]);

                return [
                    'status'  => true,
                    'message' => 'gift success',
                ];
            }

            /** @var PaymentProcess $paymentProcess */
            if ($paymentProcess->model_type === MemberShip::class && $status === Transaction::STATUS_PAID) {

                /** @var MemberShip $model */
                $model = $paymentProcess->model;

                $userMemberShip = UserMemberShip::where([
                    ['member_ship_id', $paymentProcess->model_id],
                    ['user_id',        $paymentProcess->user_id],
                    ['created_at', '>=', date('Y-m-d H:i:s', strtotime('-10 second'))],
                ])->first();

                if (empty($userMemberShip)) {
                    $userMemberShip = UserMemberShip::create([
                        'member_ship_id' => $paymentProcess->model_id,
                        'user_id'        => $paymentProcess->user_id,
                        'color'          => $model->color,
                        'price'          => $model->price,
                        'expired_at'     => date('Y-m-d H:i:s', strtotime("+$model->time")),
                        'sessions'       => $model->sessions,
                        'sessions_count' => $model->sessions_count,
                        'remainder'      => $model->sessions_count,
                    ]);
                }

                $userMemberShip->createTransaction([
                    'price'          => $model->price,
                    'payment_sys_id' => data_get($paymentProcess->data, 'payment_id'),
                    'user_id'        => $paymentProcess->user_id,
                    'payment_trx_id' => $token,
                    'status'         => $status,
                ]);

                return [
                    'status'  => true,
                    'message' => 'membership success',
                ];
            }

            if ($paymentProcess->model_type === Wallet::class && $status === Transaction::STATUS_PAID) {

                $totalPrice = (double)data_get($paymentProcess->data, 'total_price') / 100;

                $user = $paymentProcess->user;

                (new WalletHistoryService)->create([
                    'type'           => 'topup',
                    'payment_sys_id' => data_get($paymentProcess->data, 'payment_id'),
                    'created_by'     => data_get($paymentProcess->data, 'created_by'),
                    'payment_trx_id' => $token,
                    'price'          => $totalPrice,
                    'note'           => __('errors.' . ResponseError::WALLET_TOP_UP, ['sender' => ''], $user?->lang ?? $this->language),
                    'status'         => WalletHistory::PAID,
                    'user'           => $user
                ]);

                return [
                    'status'  => true,
                    'message' => 'wallet success',
                ];
            }

            if ($paymentProcess->model_type !== Booking::class) {
                $paymentProcess->model?->transaction?->update(['payment_trx_id' => $token, 'status' => $status]);
            }

            if ($paymentProcess->model_type === ShopAdsPackage::class) {

                $time = $paymentProcess->model?->adsPackage?->time ?? 1;
                $type = $paymentProcess->model?->adsPackage?->time_type ?? 'day';

                $paymentProcess->model->createTransaction([
                    'price'          => $paymentProcess->model?->adsPackage?->price ?? 1,
                    'payment_sys_id' => data_get($paymentProcess->data, 'payment_id'),
                    'user_id'        => $paymentProcess->user_id,
                    'payment_trx_id' => $token,
                    'status'         => $status,
                ]);

                if ($status === Transaction::STATUS_PAID) {
                    $paymentProcess->model->update([
                        'active'     => true,
                        'expired_at' => date('Y-m-d H:i:s', strtotime("+$time $type"))
                    ]);
                }

                return [
                    'status'  => true,
                    'message' => 'success',
                ];
            }

            if ($paymentProcess->model_type === Cart::class) {

                $paymentProcess->update([
                    'data' => array_merge($paymentProcess->data, ['trx_status' => $status])
                ]);

                if ($status === Transaction::STATUS_PAID) {
                    (new OrderService)->create($paymentProcess->data);

                }

                if (isset($paymentProcess->data['cart_id'])) {

                    $admins = User::whereHas('roles', fn($q) => $q->where('name', 'admin') )
                        ->whereNotNull('firebase_token')
                        ->select(['id', 'lang', 'firebase_token'])
                        ->get();

                    Order::with(['transaction', 'shop'])
                        ->where('cart_id', $paymentProcess->data['cart_id'])
                        ->get()
                        ->map(function (Order $order) use ($admins) {

                            try {

                                $this->sendUsers($order, $admins);

                                if ($order->shop?->user_id) {
                                    $seller = User::select(['firebase_token', 'id', 'lang'])->find($order->shop->user_id);
                                    $this->sendUsers($order, [$seller]);
                                }

                            } catch (Throwable $e) {
                                Log::error($e->getMessage());
                            }

                            try {

                                $order->transaction?->update(['price'  => $order->total_price]);

                            } catch (Throwable $e) {
                                Log::error($e->getMessage());
                            }

                        });
                }
            }

            if ($paymentProcess->model_type === Booking::class && !isset($paymentProcess->data['tips']) && !isset($paymentProcess->data['extra_time'])) {

                $paymentProcess->update([
                    'data' => array_merge($paymentProcess->data, ['trx_status' => $status])
                ]);

                $model = $paymentProcess->model->load(['children:id,parent_id', 'children.transaction']);

                $model->transaction?->update(['payment_trx_id' => $token, 'status' => $status]);

                foreach ($model->children as $child) {
                    $child->transaction?->update(['payment_trx_id' => $token, 'status' => $status]);
                }

            } elseif ($paymentProcess->model_type === Booking::class && isset($paymentProcess->data['tips'])) {

                $paymentProcess->update([
                    'data' => array_merge($paymentProcess->data, ['tips_trx_status' => $status])
                ]);

                $paymentProcess->model?->update(['tips' => $paymentProcess->data['tips']]);

            } elseif ($paymentProcess->model_type === Booking::class && isset($paymentProcess->data['extra_time'])) {

                $paymentProcess->update([
                    'data' => array_merge($paymentProcess->data, ['extra_time_trx_status' => $status])
                ]);

                /** @var Booking $booking */
                $booking = $paymentProcess->model;

                /** @var BookingExtraTime $extraTime */
                $extraTime = $booking->extraTimes()->create([
                    'price'         => $paymentProcess->data['total_price'],
                    'duration'      => $paymentProcess->data['duration'],
                    'duration_type' => $paymentProcess->data['duration_type'],
                ]);

                $booking->update([
                    'extra_time_price' => $extraTime->price,
                    'total_price'      => $booking->total_price + $extraTime->price,
                ]);

                $booking->transaction()->update([
                    'price' => $booking->total_price,
                ]);

                $unit = "+$extraTime->duration $extraTime->duration_type";

                $booking->update([
                    'end_date' => $booking->end_date->add($unit)->format('Y-m-d H:i:s')
                ]);

            }

            if ($paymentProcess->model_type === ParcelOrder::class) {

                $transaction = $paymentProcess->model?->transaction?->where('status',Transaction::STATUS_PAID)->first();

                if($transaction) {
                    $transaction->update([
                        'status' => Transaction::STATUS_REFUND
                    ]);
                }

                (new TransactionService)->orderTransaction($paymentProcess->model_id, [
                    'payment_sys_id' => data_get($paymentProcess->data, 'payment_id'),
                    'payment_trx_id' => $paymentProcess->id,
                ], ParcelOrder::class);

            }

            return [
                'status'  => true,
                'message' => 'success',
            ];
        } catch (Throwable $e) {
            return [
                'status'  => false,
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTrace()
            ];
        }
    }

    /**
     * @param array $data
     * @param array $payload
     * @return array
     * @throws Exception
     */
    public function getPayload(array $data, array $payload): array
    {
        $key    = '';
        $before = [];

        if (data_get($data, 'cart_id')) {

            $key = 'cart_id';
            $before = $this->beforeCart($data, $payload);

        } else if (data_get($data, 'booking_id') && !isset($data['extra_time'])) {

            $key = 'booking_id';
            $before = $this->beforeBooking($data, $payload);

        } else if (data_get($data, 'booking_id') && isset($data['extra_time'])) {

            $key = 'booking_id';
            $before = $this->beforeBooking($data, $payload);

        } else if (data_get($data, 'member_ship_id')) {

            $key = 'member_ship_id';
            $before = $this->beforeMemberShip($data, $payload);

        } else if (data_get($data, 'parcel_id')) {

            $key = 'parcel_id';
            $before = $this->beforeParcel($data, $payload);

        } else if (data_get($data, 'subscription_id')) {

            $key = 'subscription_id';
            $before = $this->beforeSubscription($data);

        } else if (data_get($data, 'ads_package_id')) {

            $key = 'ads_package_id';
            $before = $this->beforePackage($data, $payload);

        } else if (data_get($data, 'wallet_id')) {

            $key = 'wallet_id';
            $before = $this->beforeWallet($data, $payload);

        } else if (data_get($data, 'gift_cart_id')) {

            $key = 'gift_cart_id';
            $before = $this->beforeGiftCart($data, $payload);

        }

        return [$key, $before];
    }

    /**
     * @param array $data
     * @param array|null $payload
     * @return array
     * @throws Exception
     */
    public function beforeCart(array $data, array|null $payload): array
    {
        $cart       = Cart::find(data_get($data, 'cart_id'));
        $calculate  = (new CartRepository)->calculateByCartId((int)data_get($data, 'cart_id'), $data);

        if (!data_get($calculate, 'status')) {
            throw new Exception('Cart is empty');
        }

        $totalPrice = round(data_get($calculate, 'data.total_price') * 100, 1);

        return [
            'model_type'  => get_class($cart),
            'model_id'    => $cart->id,
            'total_price' => $totalPrice,
            'currency'    => $cart->currency?->title ?? data_get($payload, 'currency'),
            'cart_id'     => $cart->id,
            'user_id'     => auth('sanctum')->id(),
            'status'      => Order::STATUS_NEW,
        ] + $data;
    }

    /**
     * @param array $data
     * @param array|null $payload
     * @return array
     * @throws Exception
     */
    public function beforeBooking(array $data, array|null $payload): array
    {
        /** @var Booking $booking */
        $booking    = Booking::with(['children'])->find(data_get($data, 'booking_id'));
        $totalPrice = ceil($booking->rate_total_price + $booking->children?->sum('rate_total_price')) * 100;

        if (isset($data['tips']) && $booking->status === Booking::STATUS_ENDED) {
            $totalPrice = $totalPrice / 100 * $data['tips'];
        }

        if (isset($data['extra_time']) && $booking->status === Booking::STATUS_PROGRESS) {
            $totalPrice = $data['price'];
        }

        return [
            'model_type'  => get_class($booking),
            'model_id'    => $booking->id,
            'total_price' => $totalPrice,
            'currency'    => $booking->currency?->title ?? data_get($payload, 'currency'),
            'booking_id'  => $booking->id,
            'user_id'     => $booking->user_id ?? auth('sanctum')->id(),
            'status'      => $booking->status ?? Booking::STATUS_PROGRESS,
        ] + $data;
    }

    /**
     * @param array $data
     * @param array|null $payload
     * @return array
     * @throws Exception
     */
    public function beforeMemberShip(array $data, array|null $payload): array
    {
        $memberShip = MemberShip::find(data_get($data, 'member_ship_id'));
        $totalPrice = ceil($memberShip->price) * 100;
        $currency   = $payload['currency'] ?? Currency::currenciesList()->where('id', $this->currency)->first()?->title;

        return [
            'model_type'     => get_class($memberShip),
            'model_id'       => $memberShip->id,
            'total_price'    => $totalPrice,
            'currency'       => $currency,
            'user_id'        => auth('sanctum')->id(),
            'member_ship_id' => $memberShip->id,
        ] + $data;
    }

    /**
     * @param array $data
     * @param array|null $payload
     * @return array
     */
    public function beforeParcel(array $data, array|null $payload): array
    {
        $parcel     = ParcelOrder::find(data_get($data, 'parcel_id'));
        $totalPrice = round($parcel->rate_total_price * 100, 1);

        return [
            'model_type'  => get_class($parcel),
            'model_id'    => $parcel->id,
            'total_price' => $totalPrice,
            'currency'    => $parcel->currency?->title ?? data_get($payload, 'currency')
        ];
    }

    /**
     * @param array $data
     * @return array
     */
    public function beforeSubscription(array $data): array
    {
        $subscription = Subscription::find(data_get($data, 'subscription_id'));
        $totalPrice   = round($subscription->price * 100, 1);

        return [
            'model_type'      => get_class($subscription),
            'model_id'        => $subscription->id,
            'currency'        => data_get($data, 'currency'),
            'total_price'     => $totalPrice,
            'shop_id'         => data_get($data, 'shop_id'),
            'subscription_id' => $subscription->id,
        ];
    }

    /**
     * @param array $data
     * @param array|null $payload
     * @return array
     */
    public function beforePackage(array $data, array|null $payload): array
    {
        $adsPackage = AdsPackage::find(data_get($data, 'ads_package_id'));
        $totalPrice = round($adsPackage->price * 100, 1);

        $model = ShopAdsPackage::updateOrCreate([
            'ads_package_id' => $adsPackage->id,
            'shop_id'        => GetShop::shop()?->id,
            'active'         => false,
        ]);

        $currency = Currency::find($this->currency);

        return [
            'model_type'  => get_class($model),
            'model_id'    => $model->id,
            'total_price' => $totalPrice,
            'currency'    => $currency?->title ?? data_get($payload, 'currency')
        ];
    }

    /**
     * @param array $data
     * @param array|null $payload
     * @return array
     */
    public function beforeWallet(array $data, array|null $payload): array
    {
        $model = Wallet::find(data_get($data, 'wallet_id'));

        $totalPrice = round((double)data_get($data, 'total_price') * 100, 1);

        $currency = Currency::find($this->currency);

        return [
            'model_type'     => get_class($model),
            'model_id'       => $model->id,
            'total_price'    => $totalPrice,
            'currency'       => $currency?->title ?? data_get($payload, 'currency')
        ];
    }

    /**
     * @param array $data
     * @param array|null $payload
     * @return array
     */
    public function beforeGiftCart(array $data, array|null $payload): array
    {
        $model = GiftCart::find($data['gift_cart_id']);

        $totalPrice = ceil($model->price * 100);

        $currency = Currency::find($this->currency);

        return [
            'model_type'  => get_class($model),
            'model_id'    => $model->id,
            'total_price' => $totalPrice,
            'currency'    => $currency?->title ?? data_get($payload, 'currency')
        ];
    }

    public function getValidateData(array $data): array
    {
        $shop     = GetShop::shop();
        $currency = Currency::currenciesList()->where('active', 1)->where('default', 1)->first()?->title;

        if ($shop?->id) {
            $data['shop_id']  = $shop->id;
            $data['currency'] = $currency;
        }

        return $data;
    }

}
