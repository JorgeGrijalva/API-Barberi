<?php
declare(strict_types=1);

namespace App\Services\PaymentService;

use App\Helpers\ResponseError;
use App\Models\Payment;
use App\Models\PaymentPayload;
use App\Models\PaymentProcess;
use App\Models\Payout;
use App\Models\User;
use Exception;
use Http;
use Request;
use Str;
use Throwable;

class PayTabsService extends BaseService
{
    protected function getModelClass(): string
    {
        return Payout::class;
    }

    /**
     * @param array $data
     * @return PaymentProcess
     * @throws Throwable
     */
    public function processTransaction(array $data): PaymentProcess
    {
        $payment        = Payment::where('tag', Payment::TAG_PAY_TABS)->first();
        $paymentPayload = PaymentPayload::where('payment_id', $payment?->id)->first();
        $payload        = $paymentPayload?->payload;

        $host = request()->getSchemeAndHttpHost();

        $headers = [
            'Accept' 		=> 'application/json',
            'Content-Type' 	=> 'application/json',
            'authorization' => data_get($payload, 'server_key')

        ];

        [$key, $before] = $this->getPayload($data, $payload);

        $modelId    = data_get($before, 'model_id');
        $totalPrice = ceil(data_get($before, 'total_price'));

        $trxRef   = "$modelId-" . time();

        $currency = Str::upper(data_get($before, 'currency'));

        if (!in_array($currency, ['AED','EGP','SAR','OMR','JOD','US'])) {
            throw new Exception(__('errors.' . ResponseError::CURRENCY_NOT_FOUND, locale: $this->language));
        }

        /** @var User $user */
        $user = auth('sanctum')->user();

        $request = Http::withHeaders($headers)->post('https://secure-egypt.paytabs.com/payment/request', [
            'profile_id'        => data_get($payload,'profile_id'),
            'tran_type'         => 'sale',
            'tran_class'        => 'ecom',
            'cart_id'        	=> $trxRef,
            'cart_description'  => data_get($data, 'note') ?? ('payment for' . str_replace(['_id', '_'], ' ', $key) . "#$modelId"),
            'cart_currency'  	=> $currency,
            'cart_amount'  		=> $totalPrice,
            'callback'          => "$host/api/v1/webhook/paytabs/payment",
            'return'          	=> "$host/payment-success?$key=$modelId&lang=$this->language",
            'customer_details'  => [
                'name'    => $user?->firstname,
                'email'   => $user?->email,
                'street1' => $user?->address?->street_house_number,
                'city'    => $user?->address?->city?->translation?->title,
                'state'   => $user?->address?->region?->translation?->title,
                'country' => $user?->address?->country?->translation?->title,
                'ip'      => Request::ip(),
            ]
        ]);

        $body = $request->json();

        if (!in_array($request->status(), [200, 201])) {
            throw new Exception(data_get($body, 'message'));
        }


        return PaymentProcess::updateOrCreate([
            'user_id'    => auth('sanctum')->id(),
            'model_type' => data_get($before, 'model_type'),
            'model_id'   => data_get($before, 'model_id'),
        ], [
            'id'    => $trxRef,
            'data'  => array_merge([
                'url'        => $body['redirect_url'],
                'payment_id' => $payment->id,
            ], $before)
        ]);
    }

}
