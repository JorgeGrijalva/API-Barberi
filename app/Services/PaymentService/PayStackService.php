<?php
declare(strict_types=1);

namespace App\Services\PaymentService;

use App\Models\Payment;
use App\Models\PaymentPayload;
use App\Models\PaymentProcess;
use App\Models\Payout;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Matscode\Paystack\Transaction;
use Str;
use Throwable;

class PayStackService extends BaseService
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
        $payment = Payment::where('tag', Payment::TAG_PAY_STACK)->first();

        $paymentPayload = PaymentPayload::where('payment_id', $payment?->id)->first();
        $payload        = $paymentPayload?->payload;

        $transaction    = new Transaction(data_get($payload, 'paystack_sk'));

        $host = request()->getSchemeAndHttpHost();

        [$key, $before] = $this->getPayload($data, $payload);
        $modelId        = data_get($before, 'model_id');

        $totalPrice     = ceil(data_get($before, 'total_price') * 2 * 100) / 2;

        $data = [
            'email'     => data_get($data, 'email', auth('sanctum')->user()?->email),
            'amount'    => $totalPrice,
            'currency'  => Str::upper(data_get($before, 'currency')),
        ];

        $response = $transaction
            ->setCallbackUrl("$host/payment-success?$key=$modelId&lang=$this->language")
            ->initialize($data);

        if (isset($response?->status) && !data_get($response, 'status')) {
            throw new Exception(data_get($response, 'message', 'PayStack server error'));
        }

        return PaymentProcess::updateOrCreate([
            'user_id'    => auth('sanctum')->id(),
            'model_type' => data_get($before, 'model_type'),
            'model_id'   => data_get($before, 'model_id'),
        ], [
            'id' => data_get($response, 'reference'),
            'data' => array_merge([
                'url'        => data_get($response, 'authorizationUrl'),
                'payment_id' => $payment->id,
            ], $before),
        ]);
    }

}
