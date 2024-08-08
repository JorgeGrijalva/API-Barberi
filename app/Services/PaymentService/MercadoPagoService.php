<?php
declare(strict_types=1);

namespace App\Services\PaymentService;

use App\Models\Payment;
use App\Models\PaymentPayload;
use App\Models\PaymentProcess;
use App\Models\Payout;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Log;
use MercadoPago\Config;
use MercadoPago\Item;
use MercadoPago\Preference;
use MercadoPago\SDK;
use Str;
use Throwable;

class MercadoPagoService extends BaseService
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
        $payment        = Payment::where('tag', Payment::TAG_MERCADO_PAGO)->first();

        $paymentPayload = PaymentPayload::where('payment_id', $payment?->id)->first();
        $payload        = $paymentPayload?->payload;

        [$key, $before] = $this->getPayload($data, $payload);

        $modelId = data_get($before, 'model_id');

        $host = request()->getSchemeAndHttpHost();

        $url  = "$host/payment-success?token={CHECKOUT_SESSION_ID}&$key=$modelId&lang=$this->language";

        $token = data_get($payload, 'token');

        SDK::setAccessToken($token);

        $sandbox = (bool)data_get($payload, 'sandbox', false);

        $config = new Config();
        $config->set('sandbox', $sandbox);
        $config->set('access_token', $token);

        $trxRef = Str::uuid();
        $item               = new Item;
        $item->id           = $trxRef;
        $item->title        = $modelId;
        $item->quantity     = 1;
        $item->unit_price   = data_get($before, 'total_price');

        $preference             = new Preference;
        $preference->items      = [$item];
        $preference->back_urls  = [
            'success' => $url,
            'failure' => $url,
            'pending' => $url
        ];

        $preference->auto_return = 'approved';

        $preference->save();

        $paymentLink = $sandbox ? $preference->sandbox_init_point : $preference->init_point;

        if (!$paymentLink) {
            throw new Exception('ERROR IN MERCADO PAGO');
        }

        Log::error('preference', [
            'id'   => $preference,
            'preference' => $preference->id,
            'preference_items' => $preference->items,
            'preference_init' => $preference->init_point
        ]);

        return PaymentProcess::updateOrCreate([
            'user_id'    => auth('sanctum')->id(),
            'model_type' => data_get($before, 'model_type'),
            'model_id'   => $modelId,
        ], [
            'id'    => $trxRef,
            'data'  => [
                'url'   => $paymentLink,
                'price' => data_get($before, 'total_price'),
            ]
        ]);
    }

}
