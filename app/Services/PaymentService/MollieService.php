<?php

namespace App\Services\PaymentService;

use App\Models\Payment;
use App\Models\PaymentPayload;
use App\Models\PaymentProcess;
use App\Models\Payout;
use Exception;
use Http;
use Illuminate\Database\Eloquent\Model;
use Str;

class MollieService extends BaseService
{
    protected function getModelClass(): string
    {
        return Payout::class;
    }

    /**
     * @param array $data
     * @return PaymentProcess|Model
     * @throws Exception
     */
    public function processTransaction(array $data): Model|PaymentProcess
    {
        $payment        = Payment::where('tag', Payment::TAG_MOLLIE)->first();
        $paymentPayload = PaymentPayload::where('payment_id', $payment?->id)->first();
        $payload        = $paymentPayload?->payload;

        $host = request()->getSchemeAndHttpHost();

        $token = data_get($payload, 'secret_key');

        $headers = [
            'Authorization' => "Bearer $token"
        ];

        [$key, $before] = $this->getPayload($data, $payload);

        $modelId    = data_get($before, 'model_id');

        $totalPrice = ceil(data_get($before, 'total_price') / 100);

        $request = Http::withHeaders($headers)
            ->post('https://api.mollie.com/v2/payments', [
                'amount'      => [
                    'value'     => "$totalPrice.00",
                    'currency'  => Str::upper(data_get($before, 'currency')),
                ],
                'description' => "Payment for products",
                'redirectUrl' => "$host/payment-success?$key=$modelId&lang=$this->language",
                'webhookUrl'  => "$host/api/v1/webhook/mollie/payment?$key=$modelId&lang=$this->language",
            ]);

        $response = $request->json();

        if (!in_array($request->status(), [200, 201])) {
            $message = data_get($response, 'title') . ': ' . data_get($response, 'detail');
            throw new Exception($message, $request->status());
        }

        return PaymentProcess::updateOrCreate([
            'user_id'    => auth('sanctum')->id(),
            'model_type' => data_get($before, 'model_type'),
            'model_id'   => data_get($before, 'model_id'),
        ], [
            'id' => data_get($response, 'id'),
            'data' => array_merge([
                'url'        => data_get($response, '_links.checkout.href'),
                'payment_id' => $payment?->id,
            ], $before)
        ]);
    }

}
