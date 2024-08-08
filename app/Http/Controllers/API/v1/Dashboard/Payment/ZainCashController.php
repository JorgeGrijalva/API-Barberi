<?php

namespace App\Http\Controllers\API\v1\Dashboard\Payment;

use App\Models\Payment;
use App\Models\Transaction;
use App\Services\PaymentService\ZainCashService;
use Firebase\JWT\JWT;
use Http;
use Illuminate\Http\Request;
use Log;

class ZainCashController extends PaymentBaseController
{
    public function __construct(private ZainCashService $service)
    {
        parent::__construct($service);
    }

    /**
     * @param Request $request
     * @return void
     */
    public function paymentWebHook(Request $request): void
    {
        Log::error('$request', $request->all());

        /** @var Payment $payment */
        $payment = Payment::with([
            'paymentPayload'
        ])
            ->where('tag', Payment::TAG_ZAIN_CASH)
            ->first();

        $payload = $payment?->paymentPayload?->payload ?? [];

        $id   = $request->input('id');
        $time = time();
        $data = [
            'id'      => $id,
            'msisdn'  => $payload['msisdn'],
            'iat'     => $time,
            'exp'     => $time + 60 * 60 * 4
        ];

        $newToken = JWT::encode($data, $payload['key'],'HS256');

        $rUrl = ($payload['url'] ?? 'https://test.zaincash.iq') . '/transaction/get';

        $response = Http::withHeaders([
            'Content-Type' => 'application/json'
        ])
            ->post($rUrl, [
                'token'      => $newToken,
                'merchantId' => $payload['merchantId'],
                'lang'       => $this->language
            ]);

        $status = match (data_get($response, 'data.0.payment_status')) {
            'succeeded', 'paid'			 => Transaction::STATUS_PAID,
            'payment_failed', 'canceled' => Transaction::STATUS_CANCELED,
            default				  		 => 'progress',
        };

        $this->service->afterHook($newToken, $status, $request->input('data.object.id'));
    }

}
