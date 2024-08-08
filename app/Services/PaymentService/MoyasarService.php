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

class MoyasarService extends BaseService
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
        $payment = Payment::where('tag', Payment::TAG_MOYA_SAR)->first();

        $paymentPayload = PaymentPayload::where('payment_id', $payment?->id)->first();
        $payload        = $paymentPayload?->payload;

        $host = request()->getSchemeAndHttpHost();

        $token = base64_encode(data_get($payload, 'secret_key'));

        $headers = [
            'Authorization' => "Basic $token"
        ];

        [$key, $before] = $this->getPayload($data, $payload);

        $modelId    = data_get($before, 'model_id');

        $totalPrice = data_get($before, 'total_price');

        $request = Http::withHeaders($headers)
            ->post('https://api.moyasar.com/v1/invoices', [
                'amount'      => $totalPrice,
                'currency'    => Str::upper(data_get($before, 'currency')),
                'description' => 'Payment for products',
                'back_url'    => "$host/payment-success?$key=$modelId&lang=$this->language",
                'success_url' => "$host/payment-success?$key=$modelId&lang=$this->language",
            ]);

        $response = $request->json();

        if (!in_array($request->status(), [200, 201])) {
            throw new Exception($request->json('message', 'error in moya-sar'));
        }

        return PaymentProcess::updateOrCreate([
            'user_id'    => auth('sanctum')->id(),
            'model_type' => data_get($before, 'model_type'),
            'model_id'   => data_get($before, 'model_id'),
        ], [
            'id' => data_get($response, 'id'),
            'data' => array_merge([
                'url'        => data_get($response, 'url'),
                'payment_id' => $payment->id,
            ], $before)
        ]);
    }

}
