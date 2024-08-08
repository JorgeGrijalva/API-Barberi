<?php
declare(strict_types=1);

namespace App\Services\PaymentService;

use App\Models\Payment;
use App\Models\PaymentPayload;
use App\Models\PaymentProcess;
use App\Models\Payout;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Srmklive\PayPal\Services\PayPal;
use Str;
use Throwable;

class PayPalService extends BaseService
{
    protected function getModelClass(): string
    {
        return Payout::class;
    }

    /**
     * @param array|null $payload
     * @return PayPal
     * @throws Throwable
     */
    public function credential(array|null $payload): PayPal
    {
        $provider = new PayPal;

        $provider->setApiCredentials([
            'mode'    => data_get($payload, 'paypal_mode', 'sandbox'),
            'sandbox' => [
                'client_id'         => data_get($payload, 'paypal_sandbox_client_id'),
                'client_secret'     => data_get($payload, 'paypal_sandbox_client_secret'),
                'app_id'            => data_get($payload, 'paypal_sandbox_app_id'),
            ],
            'live' => [
                'client_id'         => data_get($payload, 'paypal_live_client_id'),
                'client_secret'     => data_get($payload, 'paypal_live_client_secret'),
                'app_id'            => data_get($payload, 'paypal_live_app_id'),
            ],
            'payment_action' => data_get($payload, 'paypal_payment_action', 'Sale'),
            'currency'       => data_get($payload, 'paypal_currency', 'USD'),
            'notify_url'     => data_get($payload, 'paypal_notify_url'),
            'locale'         => (bool)data_get($payload, 'paypal_locale', true),
            'validate_ssl'   => (bool)data_get($payload, 'paypal_validate_ssl', true),
        ]);

        $provider->getAccessToken();

        return $provider;
    }

    /**
     * @param array $data
     * @return PaymentProcess
     * @throws GuzzleException
     * @throws Exception
     */
    public function processTransaction(array $data): PaymentProcess
    {
        $payment        = Payment::where('tag', Payment::TAG_PAY_PAL)->first();
        $paymentPayload = PaymentPayload::where('payment_id', $payment?->id)->first();

        $payload        = $paymentPayload?->payload;

        $url            = 'https://api-m.sandbox.paypal.com';
        $clientId       = data_get($payload, 'paypal_sandbox_client_id');
        $clientSecret   = data_get($payload, 'paypal_sandbox_client_secret');

        if (data_get($payload, 'paypal_mode', 'sandbox') === 'live') {
            $url            = 'https://api-m.paypal.com';
            $clientId       = data_get($payload, 'paypal_live_client_id');
            $clientSecret   = data_get($payload, 'paypal_live_client_secret');
        }

        $provider = new Client();
        $responseAuth = $provider->post("$url/v1/oauth2/token", [
            'auth' => [
                $clientId,
                $clientSecret,
            ],
            'form_params' => [
                'grant_type' => 'client_credentials',
            ]
        ]);

        $responseAuth = json_decode($responseAuth->getBody()->getContents(), true);

        [$key, $before] = $this->getPayload($data, $payload);

        $tokenType   = data_get($responseAuth, 'token_type', 'Bearer');
        $accessToken = data_get($responseAuth, 'access_token');

        $response = $provider->post("$url/v2/checkout/orders", [
            'headers' => [
                'Accept-Language' => 'en_US',
                'Content-Type'    => 'application/json',
                'Authorization'   => "$tokenType $accessToken",
            ],
        ]);

        $response = json_decode($response->getBody()->getContents(), true);

        if (data_get($response, 'error')) {

            $message = data_get($response, 'message', 'Something went wrong');

            $message = implode(',', is_array($message) ? $message : [$message]);

            throw new Exception($message, 400);
        }

        $links = collect(data_get($response, 'links'));

        $checkoutNowUrl = $links->where('rel', 'approve')->first()?->href;
        $checkoutNowUrl = $checkoutNowUrl ?? $links->where('rel', 'payer-action')->first()?->href;
        $checkoutNowUrl = $checkoutNowUrl ?? $links->first()?->href;

        return PaymentProcess::updateOrCreate([
            'user_id'    => auth('sanctum')->id(),
            'model_type' => data_get($before, 'model_type'),
            'model_id'   => data_get($before, 'model_id'),
        ], [
            'id' => data_get($response, 'id'),
            'data' => array_merge([
                'url'        => $checkoutNowUrl,
                'payment_id' => $payment->id,
            ], $before)
        ]);

    }

}
