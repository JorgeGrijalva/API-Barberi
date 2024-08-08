<?php

namespace App\Services\PaymentService;

use App\Models\Payment;
use App\Models\PaymentPayload;
use App\Models\PaymentProcess;
use App\Models\Payout;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Maksekeskus\Maksekeskus;
use Str;
use Throwable;

class MaksekeskusService extends BaseService
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
        $payment        = Payment::where('tag', Payment::TAG_MAKSEKESKUS)->first();
        $paymentPayload = PaymentPayload::where('payment_id', $payment?->id)->first();
        $payload        = $paymentPayload->payload;
        $host           = request()->getSchemeAndHttpHost();

        [$key, $before] = $this->getPayload($data, $payload);

        $modelId = data_get($before, 'model_id');

        $shopId         = $payload['shop_id'];
        $keyPublishable = $payload['key_publishable'];
        $keySecret      = $payload['key_secret'];

        $MK = new Maksekeskus($shopId, $keyPublishable, $keySecret, (bool)$payload['demo']);

        $email = auth('sanctum')->user()?->email;

        $body = [
            'transaction' => [
                'amount'        => data_get($before, 'total_price') / 100,
                'currency'      => Str::upper(data_get($before, 'currency')),
                'id'            => $modelId,
                'reference'     => auth('sanctum')->user()->full_name . " #$modelId",
                'merchant_data' => auth('sanctum')->user()->full_name . " #$modelId",
            ],
            'customer' => [
                'email'   => $email ?? Str::random(8) . '@gmail.com',
                'ip'      => request()->ip(),
                'country' => $payload['country'],
                'locale'  => $payload['country'],
            ],
            'app_info' => [
                'module'            => 'E-Commerce',
                'module_version'    => '1.0.1',
                'platform'          => 'Web',
                'platform_version'  => '2.0'
            ],
            'return_url' => "$host/payment-success?$key=$modelId&lang=$this->language&status=success",
            'cancel_url' => "$host/payment-success?$key=$modelId&lang=$this->language&status=canceled",
            'notification_url' => "$host/api/v1/webhook/maksekeskus/payment",
            'transaction_url' => [
                'return_url' => [
                    'url'    => "$host/payment-success?$key=$modelId&lang=$this->language&status=success",
                    'method' => 'POST',
                ],
                'cancel_url' => [
                    'url'    => "$host/payment-success?$key=$modelId&lang=$this->language&status=canceled",
                    'method' => 'POST',
                ],
                'notification_url' => [
                    'url'    => "$host/api/v1/webhook/maksekeskus/payment",
                    'method' => 'POST',
                ],
            ],
        ];

        try {
            $data = $MK->createTransaction($body);
        } catch (Throwable $e) {
            throw new Exception($e->getMessage());
        }

        return PaymentProcess::updateOrCreate([
            'user_id'    => auth('sanctum')->id(),
            'model_type' => data_get($before, 'model_type'),
            'model_id'   => data_get($before, 'model_id'),
        ], [
            'id' => data_get($data, 'id'),
            'data' => array_merge([
                'methods'    => data_get($data, 'payment_methods.banklinks'),
                'payment_id' => $payment?->id,
            ], $before)
        ]);
    }

}
