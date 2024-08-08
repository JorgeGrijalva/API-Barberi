<?php
declare(strict_types=1);

namespace App\Services\PaymentService;

use App\Models\Payment;
use App\Models\PaymentPayload;
use App\Models\PaymentProcess;
use App\Models\Payout;
use DB;
use Illuminate\Database\Eloquent\Model;
use Razorpay\Api\Api;
use Razorpay\Api\PaymentLink;
use Str;
use Throwable;

class RazorPayService extends BaseService
{
    protected function getModelClass(): string
    {
        return Payout::class;
    }

    /**
     * @param array $data
     * @return PaymentProcess|Model
     * @throws Throwable
     */
    public function processTransaction(array $data): Model|PaymentProcess
    {
        return DB::transaction(function () use ($data) {

            $host           = request()->getSchemeAndHttpHost();

            $payment        = Payment::where('tag', Payment::TAG_RAZOR_PAY)->first();
            $paymentPayload = PaymentPayload::where('payment_id', $payment?->id)->first();
            $payload        = $paymentPayload->payload;

            $razorpayKey    = data_get($paymentPayload?->payload, 'razorpay_key');
            $razorpaySecret = data_get($paymentPayload?->payload, 'razorpay_secret');

            $api            = new Api($razorpayKey, $razorpaySecret);

            [$key, $before] = $this->getPayload($data, $payload);

            $modelId        = data_get($before, 'model_id');
            $totalPrice     = ceil(data_get($before, 'total_price') * 2 * 100) / 2;

            $paymentLink    = $api->paymentLink->create([
                'amount'                    => $totalPrice,
                'currency'                  => Str::upper(data_get($before, 'currency')),
                'accept_partial'            => false,
                'first_min_partial_amount'  => $totalPrice,
                'description'               => "Payment for products",
                'callback_url'              => "$host/payment-success?$key=$modelId&lang=$this->language",
                'callback_method'           => 'get'
            ]);

            return PaymentProcess::updateOrCreate([
                'user_id'    => auth('sanctum')->id(),
                'model_type' => data_get($before, 'model_type'),
                'model_id'   => data_get($before, 'model_id'),
            ], [
                'id'    => data_get($paymentLink, 'id'),
                'data'  => array_merge([
                    'url'        => data_get($paymentLink, 'short_url'),
                    'payment_id' => $payment->id,
                ], $before)
            ]);
        });
    }

}
