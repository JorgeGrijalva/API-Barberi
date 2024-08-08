<?php

namespace App\Http\Controllers\API\v1\Dashboard\Payment;

use App\Models\AdsPackage;
use App\Models\Cart;
use App\Models\ParcelOrder;
use App\Models\PaymentProcess;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\PaymentService\MollieService;
use Illuminate\Http\Request;
use Log;

class MollieController extends PaymentBaseController
{
    public function __construct(private MollieService $service)
    {
        parent::__construct($service);
    }

    /**
     * @param Request $request
     * @return array
     */
    public function paymentWebHook(Request $request): array
    {
        $status = $request->input('status');

        $status = match ($status) {
            'paid'                          => Transaction::STATUS_PAID,
            'canceled', 'expired', 'failed' => Transaction::STATUS_CANCELED,
            default                         => 'progress',
        };

        Log::error('paymentWebHook', $request->all());

        $parcelId       = (int)$request->input('parcel_id');
        $adsPackageId   = (int)$request->input('ads_package_id');
        $subscriptionId = (int)$request->input('subscription_id');
        $walletId       = (int)$request->input('wallet_id');

        $class = Cart::class;
        $id    = (int)$request->input('cart_id');

        if ($parcelId) {
            $class  = ParcelOrder::class;
            $id     = $parcelId;
        } else if ($adsPackageId) {
            $class  = AdsPackage::class;
            $id     = $adsPackageId;
        } else if ($subscriptionId) {
            $class  = Subscription::class;
            $id     = $subscriptionId;
        } else if ($walletId) {
            $class  = Wallet::class;
            $id     = $walletId;
        }

        $paymentProcess = PaymentProcess::where([
            'model_type' => $class,
            'model_id'   => $id,
        ])->first();

        return $this->service->afterHook($paymentProcess->id, $status);
    }

}
