<?php

namespace App\Http\Controllers\API\v1\Dashboard\Payment;

use App\Helpers\ResponseError;
use App\Http\Requests\Payment\TransactionRequest;
use App\Http\Requests\Payment\TransactionUpdateRequest;
use App\Http\Resources\OrderResource;
use App\Http\Resources\ParcelOrderResource;
use App\Http\Resources\SubscriptionResource;
use App\Http\Resources\UserGiftCartResource;
use App\Http\Resources\UserMemberShipResource;
use App\Http\Resources\WalletResource;
use App\Models\Booking;
use App\Models\Order;
use App\Models\ParcelOrder;
use App\Models\ShopAdsPackage;
use App\Models\ShopSubscription;
use App\Models\Transaction;
use App\Models\UserGiftCart;
use App\Models\UserMemberShip;
use App\Models\Wallet;
use App\Services\TransactionService\TransactionService;
use Illuminate\Http\JsonResponse;

class TransactionController extends PaymentBaseController
{

    public function __construct(private TransactionService $service)
    {
        parent::__construct($service);
    }

    public function store(string $type, int $id, TransactionRequest $request): JsonResponse
    {
        if ($type === 'order') {

            $result = $this->service->orderTransaction($id, $request->validated());

            if (!data_get($result, 'status')) {
                return $this->onErrorResponse($result);
            }

            return $this->successResponse(
                __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
                OrderResource::make(data_get($result, 'data'))
            );

        } else if ($type === 'parcel-order') {

            $result = $this->service->orderTransaction($id, $request->validated(), ParcelOrder::class);

            if (!data_get($result, 'status')) {
                return $this->onErrorResponse($result);
            }

            return $this->successResponse(
                __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language),
                ParcelOrderResource::make(data_get($result, 'data'))
            );

        } else if ($type === 'subscription') {
            $result = $this->service->subscriptionTransaction($id, $request->validated());

            if (!data_get($result, 'status')) {
                return $this->onErrorResponse($result);
            }

            return $this->successResponse(
                __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language),
                SubscriptionResource::make(data_get($result, 'data'))
            );
        } else if ($type === 'ads') {
            $result = $this->service->adsTransaction($id, $request->validated());

            if (!data_get($result, 'status')) {
                return $this->onErrorResponse($result);
            }

            return $this->successResponse(
                __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language),
                SubscriptionResource::make(data_get($result, 'data'))
            );
        } else if ($type === 'gift-cart') {
            $result = $this->service->giftCartTransaction($id, $request->validated());

            if (!data_get($result, 'status')) {
                return $this->onErrorResponse($result);
            }

            return $this->successResponse(
                __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language),
                UserGiftCartResource::make(data_get($result, 'data'))
            );
        } else if ($type === 'member-ship') {
            $result = $this->service->memberShipTransaction($id, $request->validated());

            if (!data_get($result, 'status')) {
                return $this->onErrorResponse($result);
            }

            return $this->successResponse(
                __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language),
                UserMemberShipResource::make(data_get($result, 'data'))
            );
        }

        $result = $this->service->walletTransaction($id, $request->validated());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language),
            WalletResource::make(data_get($result, 'data'))
        );
    }

    public function updateStatus(string $type, int $id, TransactionUpdateRequest $request): JsonResponse
    {
        /** @var Order $model */
        $model = match($type) {
            'parcel-order'  => ParcelOrder::with('transaction')->find($id),
            'subscription'  => ShopSubscription::with('transaction')->find($id),
            'ads-package', 'ads' => ShopAdsPackage::with('transaction')->find($id),
            'wallet'        => Wallet::with(['transaction' => fn($q) => $q->orderBy('id', 'desc')])->find($id),
            'booking'       => Booking::with(['transaction' => fn($q) => $q->orderBy('id', 'desc')])->find($id),
            'member-ship'   => UserMemberShip::with(['transaction' => fn($q) => $q->orderBy('id', 'desc')])->find($id),
            'gift-cart'     => UserGiftCart::with(['transaction' => fn($q) => $q->orderBy('id', 'desc')])->find($id),
            default         => Order::with('transaction')->find($id),
        };

        if (!$model) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        if (!$model->transaction) {
            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_501,
                'message' => __('errors.' . ResponseError::ERROR_501, locale: $this->language)
            ]);
        }

//        $paymentProcess = PaymentProcess::find($request->input('token'));
//
//        if (empty($paymentProcess) && !in_array($order->transaction->paymentSystem?->tag, ['cash', 'wallet'])) {
//            return $this->onErrorResponse([
//                'code'    => ResponseError::ERROR_400,
//                'message' => 'Order not paid'
//            ]);
//        }

        /** @var Transaction $transaction */
        $model->transaction->update([
            'status' => $request->input('status')
        ]);

//        $paymentProcess?->delete();

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            $model->fresh('transaction')
        );
    }
}
