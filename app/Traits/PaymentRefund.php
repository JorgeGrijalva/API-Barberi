<?php
declare(strict_types=1);

namespace App\Traits;

use App\Models\Order;
use App\Models\ParcelOrder;
use App\Models\Payment;
use App\Models\ShopAdsPackage;
use App\Models\Transaction;
use Exception;
use GuzzleHttp\Client;
use Http;
use Illuminate\Database\Eloquent\Collection;
use Iyzipay\Model\Refund;
use Iyzipay\Options;
use Iyzipay\Request\CreateRefundRequest;
use Maksekeskus\MKException;
use Razorpay\Api\Api;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;
use Maksekeskus\Maksekeskus;
use Throwable;

/**
 * @property-read Transaction|null $transaction
 * @property-read Collection|Transaction[] $transactions
 * @property-read int $transactions_count
 * */
trait PaymentRefund
{
    /**
     * @throws ApiErrorException
     * @throws Exception
     * @throws Throwable
     */
    public function paymentRefund($model): void
    {
        /** @var Order $model */

        switch ($model?->transaction?->paymentSystem?->tag) {
            case Payment::TAG_STRIPE:
                $this->stripeRefund($model);
                break;
            case Payment::TAG_FLUTTER_WAVE:
                $this->flutterWaveRefund($model);
                break;
            case Payment::TAG_PAY_STACK:
                $this->payStackRefund($model);
                break;
            case Payment::TAG_RAZOR_PAY:
                $this->razorPayRefund($model);
                break;
            case Payment::TAG_MOYA_SAR:
                $this->moyasarRefund($model);
                break;
            case Payment::TAG_MOLLIE:
                $this->mollieRefund($model);
                break;
            case Payment::TAG_MAKSEKESKUS:
                $this->maksekeskusRefund($model);;
                break;
            case Payment::TAG_IYZICO:
                $this->iyzicoRefund($model);;
                break;
            case Payment::TAG_PAY_TABS:
                $this->paytabsRefund($model);;
                break;
            case Payment::TAG_PAY_PAL:
                $this->paypalRefund($model);;
                break;
        }

    }

    /**
     * @throws ApiErrorException
     * @throws Exception
     */
    protected function stripeRefund($model): void
    {
        $modelData = $this->getModelData($model);

        $stripe = new StripeClient(data_get($modelData['payload'], 'stripe_sk'));

        $response = $stripe->refunds->create([
            'payment_intent' => $modelData['transactionId'],
            'amount' => round(round($modelData['price'], 2) * 100, 1)
        ]);

        if (isset($response?->status) && $response->status == 'succeeded') {
            $model->transaction->update([
                'status' => Transaction::STATUS_REFUND
            ]);
        }
    }

    /**
     * @throws Exception
     */
    protected function flutterWaveRefund($model): void
    {
        $modelData = $this->getModelData($model);

        $headers = [
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . data_get($modelData['payload'], 'flw_sk')
        ];

        $data = [
            'amount' => $modelData['price']
        ];

        $request = Http::withHeaders($headers)->post('https://api.flutterwave.com/v3/transactions/'.$modelData['transactionId'].'/refund',$data);

        if ($request->status() == 200) {
            $status = $request->json('data.status');

            if ($status == 'completed') {
                $model->transaction->update([
                    'status' => Transaction::STATUS_REFUND
                ]);
            }

        }
    }

    /**
     * @throws Exception
     */
    protected function payStackRefund($model): void
    {
        $modelData = $this->getModelData($model);

        $headers = [
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . data_get($modelData['payload'], 'flw_sk')
        ];

        $data = [
            'amount'      => $modelData['price'],
            'transaction' => $modelData['transactionId']
        ];

        $request = Http::withHeaders($headers)->post('https://api.paystack.co/refund',$data);

        if ($request->status() == 200) {
            $model->transaction->update([
                'status' => Transaction::STATUS_PROGRESS
            ]);
        }
    }


    protected function razorPayRefund($model): void
    {
        $modelData      = $this->getModelData($model);
        $razorpayKey    = data_get($modelData['payload'], 'razorpay_key');
        $razorpaySecret = data_get($modelData['payload'], 'razorpay_secret');

        $api           = new Api($razorpayKey, $razorpaySecret);
        $price         = $modelData['price'];
        $transactionId = $modelData['transactionId'];

        $request = $api->payment->fetch($transactionId)->refund([
            "amount" => $price,
            "speed"  => 'normal',
            "notes"  => [
                "notes_key_1" => "Beam me up Scotty.",
                "notes_key_2" => "Engage"
            ],
            "receipt" => "Receipt No. 31"
        ]);

        if ($request->status() == 200) {
            $model->transaction->update([
                'status' => Transaction::STATUS_PROGRESS
            ]);
        }
    }

    protected function moyasarRefund($model): void
    {
        $modelData      = $this->getModelData($model);

        $token = base64_encode(data_get($modelData['payload'], 'secret_key'));

        $headers = [
            'Authorization' => "Basic $token"
        ];

        $transactionId = $modelData['transactionId'];

        $price = $modelData['price'];

        $request = Http::withHeaders($headers)
            ->post('https://api.moyasar.com/v1/payments/'.$transactionId.'/refund', [
                'amount' => $price,
            ]);

        if ($request->status() == 200 && $request->json('status') == 'refunded') {
            $model->transaction->update([
                'status' => Transaction::STATUS_REFUND
            ]);
        }
    }

    protected function mollieRefund($model): void
    {
        $modelData      = $this->getModelData($model);

        $token = data_get($modelData['payload'], 'secret_key');

        $headers = [
            'Authorization' => "Bearer $token"
        ];

        $transactionId = $modelData['transactionId'];

        $price = $modelData['price'];

        $request = Http::withHeaders($headers)
            ->post('https://api.mollie.com/v1/payments/'.$transactionId.'/refund', [
                'amount' => "$price.00",
            ]);

        if ($request->status() == 200 && $request->json('status') == 'refunded') {
            $model->transaction->update([
                'status' => Transaction::STATUS_REFUND
            ]);
        }
    }

    /**
     * @throws MKException
     */
    protected function maksekeskusRefund($model): void
    {
        $modelData      = $this->getModelData($model);
        $shopId         = $modelData['payload']['shop_id'];
        $keyPublishable = $modelData['payload']['key_publishable'];
        $keySecret      = $modelData['payload']['key_secret'];

        $MK = new Maksekeskus($shopId, $keyPublishable, $keySecret, (bool)$modelData['payload']['demo']);

        $transactionId = $modelData['transactionId'];
        $price = $modelData['price'];

        $body = [
            'amount' => $price,
        ];

        $request = $MK->createRefund($transactionId,$body);

        if ($request->status() == 200 && $request->json('status') == 'SETTLED') {
            $model->transaction->update([
                'status' => Transaction::STATUS_REFUND
            ]);
        }
    }

    /**
     * @throws MKException
     * @throws Exception
     */
    protected function iyzicoRefund($model): void
    {
        $modelData      = $this->getModelData($model);

        $options = new Options();
        $options->setApiKey(data_get($modelData['payload'], 'api_key'));
        $options->setSecretKey(data_get($modelData['payload'], 'secret_key'));
        $options->setBaseUrl('https://api.iyzipay.com');

        $refundRequest = new CreateRefundRequest();
        $refundRequest->setPaymentTransactionId($modelData['transactionId']);
        $refundRequest->setPrice($modelData['price']);
        $refund = new Refund();
        $refund->create($refundRequest,$options);

        if ($refund->getErrorCode()) {
            throw new Exception($refund->getErrorMessage());
        }
        if ($refund->getStatus() == 200) {
            $model->transaction->update([
                'status' => Transaction::STATUS_REFUND
            ]);
        }
    }

    /**
     * @throws MKException
     * @throws Exception
     */
    protected function paytabsRefund($model): void
    {
        $modelData      = $this->getModelData($model);

        $headers = [
            'Accept' 		=> 'application/json',
            'Content-Type' 	=> 'application/json',
            'authorization' => data_get($modelData['payload'], 'api_key')
        ];

        $request = Http::withHeaders($headers)->post('https://secure-egypt.paytabs.com/payment/request', [
            'tran_type' => 'refund',
            'tran_ref'  => $modelData['transactionId'],
        ]);

        if ($request->status() == 200) {
            $model->transaction->update([
                'status' => Transaction::STATUS_REFUND
            ]);
        }
    }

    /**
     * @throws MKException
     * @throws Exception
     * @throws Throwable
     */
    protected function paypalRefund($model): void
    {
        $modelData      = $this->getModelData($model);

        $url            = 'https://api-m.sandbox.paypal.com';
        $clientId       = data_get($modelData['payload'], 'paypal_sandbox_client_id');
        $clientSecret   = data_get($modelData['payload'], 'paypal_sandbox_client_secret');

        if (data_get($modelData['payload'], 'paypal_mode', 'sandbox') === 'live') {
            $url            = 'https://api-m.paypal.com';
            $clientId       = data_get($modelData['payload'], 'paypal_live_client_id');
            $clientSecret   = data_get($modelData['payload'], 'paypal_live_client_secret');
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

        $tokenType   = data_get($responseAuth, 'token_type', 'Bearer');
        $accessToken = data_get($responseAuth, 'access_token');

        $response = $provider->post("$url/v2/payments/captures/".$modelData['transactionId'].'/refund', [
            'headers' => [
                'Accept-Language' => 'en_US',
                'Content-Type'    => 'application/json',
                'Authorization'   => "$tokenType $accessToken",
            ],
        ]);

        $response = json_decode($response->getBody()->getContents(), true);

        if ($response->status() == 200 && $response->json('status') == 'COMPLETED') {
            $model->transaction->update([
                'status' => Transaction::STATUS_REFUND
            ]);
        }
    }

    protected function getModelData($model): array
    {
        /** @var Order $model */

        $transactionId = $model?->transaction?->payment_trx_id;
        $payload = $model?->transaction?->paymentSystem?->paymentPayload?->payload;
        $price = $model?->transaction?->price;

        return [
            'transactionId' => $transactionId,
            'payload'       => $payload,
            'price'         => $price
        ];
    }
}
