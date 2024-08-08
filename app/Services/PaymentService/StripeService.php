<?php
declare(strict_types=1);

namespace App\Services\PaymentService;

use App\Models\Payment;
use App\Models\PaymentProcess;
use App\Models\Payout;
use Illuminate\Database\Eloquent\Model;
use Str;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;
use Throwable;

class StripeService extends BaseService
{
    protected function getModelClass(): string
    {
        return Payout::class;
    }

    /**
     * @param array $data
     * @return PaymentProcess|Model
     * @throws ApiErrorException|Throwable
     */
    public function processTransaction(array $data): Model|PaymentProcess
    {
        /** @var Payment $payment */
        $payment = Payment::with([
            'paymentPayload'
        ])
            ->where('tag', Payment::TAG_STRIPE)
            ->first();

        $payload = $payment?->paymentPayload?->payload;

        Stripe::setApiKey(data_get($payload, 'stripe_sk'));

        [$key, $before] = $this->getPayload($data, $payload);

        $host = request()->getSchemeAndHttpHost();

        $modelId = data_get($before, 'model_id');

        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [
                [
                    'price_data' => [
                        'currency' => Str::lower(data_get($before, 'currency')),
                        'product_data' => [
                            'name' => 'Payment'
                        ],
                        'unit_amount' => data_get($before, 'total_price'),
                    ],
                    'quantity' => 1,
                ]
            ],
            'mode' => 'payment',
            'success_url' => "$host/payment-success?token={CHECKOUT_SESSION_ID}&$key=$modelId&lang=$this->language",
            'cancel_url'  => "$host/payment-success?token={CHECKOUT_SESSION_ID}&$key=$modelId&lang=$this->language",
        ]);

        return PaymentProcess::updateOrCreate([
            'user_id'    => auth('sanctum')->id(),
            'model_type' => data_get($before, 'model_type'),
            'model_id'   => $modelId,
        ], [
            'id' => $session->payment_intent ?? $session->id,
            'data' => array_merge([
                'url'        => $session->url,
                'payment_id' => $payment->id,
            ], $before)
        ]);
    }

}
