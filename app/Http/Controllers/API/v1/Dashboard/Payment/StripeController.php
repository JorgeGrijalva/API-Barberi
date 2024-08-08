<?php

namespace App\Http\Controllers\API\v1\Dashboard\Payment;

use App\Models\Payment;
use App\Models\PaymentPayload;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\PaymentService\StripeService;
use Http;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Redirect;

class StripeController extends PaymentBaseController
{
    public function __construct(private StripeService $service)
    {
        parent::__construct($service);
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function resultTransaction(Request $request): RedirectResponse
    {
        $status         = $request->input('status');
        $parcelId       = (int)$request->input('parcel_id');
        $adsPackageId   = (int)$request->input('ads_package_id');
        $subscriptionId = (int)$request->input('subscription_id');
        $walletId       = (int)$request->input('wallet_id');
        $bookingId      = (int)$request->input('booking_id');
        $giftCartId     = (int)$request->input('gift_cart_id');
        $memberShipId   = (int)$request->input('member_ship_id');

        csrf_token();

        $to = config('app.front_url') . ($status === 'error' ? 'payment/error' : '');

        if ($parcelId) {
            $to = config('app.front_url') . "parcels/$parcelId";
        } else if ($bookingId) {
            $to = config('app.front_url') . 'appointments';
        } else if ($adsPackageId) {
            $to = config('app.admin_url');
        } else if ($subscriptionId) {
            $to = config('app.admin_url');
        } else if ($giftCartId) {
            $to = config('app.front_url') . 'gift-cards';
        } else if ($memberShipId) {
            $to = config('app.front_url') . 'memberships';
        } else if ($walletId) {

            /** @var Wallet $wallet */
            $wallet = Wallet::with('user.roles')->find($walletId);

            $to = config('app.front_url') . 'wallet';

            if ($wallet?->user?->hasRole(['seller', 'admin', 'moderator', 'deliveryman', 'manager'])) {
                $to = config('app.admin_url');
            }

        }

        return Redirect::to($to);
    }

    public function iyzico3d(Request $request): Application|Factory|View
    {
        return view('iyzico', $request->all());
    }

    /**
     * @param Request $request
     * @return array
     */
    public function paymentWebHook(Request $request): array
    {
        $token = $request->input('data.object.id');

        $payment = Payment::where('tag', Payment::TAG_STRIPE)->first();

        $paymentPayload = PaymentPayload::where('payment_id', $payment?->id)->first();
        $payload        = $paymentPayload?->payload;

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . data_get($payload, 'stripe_sk')
        ])
            ->get("https://api.stripe.com/v1/checkout/sessions?limit=1&payment_intent=$token")
            ->json();

        $token = data_get($response, 'data.0.id');

        $status = match (data_get($response, 'data.0.payment_status')) {
            'succeeded', 'paid'			 => Transaction::STATUS_PAID,
            'payment_failed', 'canceled' => Transaction::STATUS_CANCELED,
            default				  		 => 'progress',
        };

        return $this->service->afterHook($token, $status, $request->input('data.object.id'));
    }

}
