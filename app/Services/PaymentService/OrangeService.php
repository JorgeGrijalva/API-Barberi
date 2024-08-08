<?php
declare(strict_types=1);

namespace App\Services\PaymentService;

use App\Models\Payment;
use App\Models\PaymentProcess;
use App\Models\Payout;
use Exception;
use Http;
use Illuminate\Database\Eloquent\Model;
use Str;
use Stripe\Exception\ApiErrorException;
use Throwable;

class OrangeService extends BaseService
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
        $payment = Payment::with(['paymentPayload'])
            ->where('tag', Payment::TAG_ORANGE)
            ->firstOrCreate([
                'active' => true,
                'input' => 15,
            ]);

        $payload = $payment?->paymentPayload?->payload ?? [];

        $token = $payload['token']['access_token'] ?? null;

        $baseUrl = $payload['url'] ?? 'https://api.sandbox.orange-sonatel.com'; // https://api.orange-sonatel.com

//        if (empty($token)) {
            $payload = $this->getToken($baseUrl, $payload);
            $token   = $payload['token']['access_token'];
            $payment?->paymentPayload()?->updateOrCreate(['payload' => $payload]);
//        }

        [$key, $before] = $this->getPayload($data, $payload);

        $host = request()->getSchemeAndHttpHost();

        $modelId = data_get($before, 'model_id');
        $trxId = Str::uuid()->toString();

        $request = Http::withHeaders([
            'Content-Type'  => 'application/json',
            'Authorization' => "Bearer $token",
        ])
            ->withoutVerifying()
            ->post("$baseUrl/api/eWallet/v4/qrcode", [
                'amount' => ['unit' => 'XOF', 'value' => 1],
                'callbackCancelUrl'  => "$host/api/v1/webhook/orange/payment",
                'callbackSuccessUrl' => "$host/api/v1/webhook/orange/payment",
                'code' => 123456,
                'metadata' => (object)[$key => $modelId],
                'name' => auth('sanctum')->user()->full_name,
                'validity' => 15,
            ])
            ->json();

//        $request = Http::withHeaders([
//            'Content-Type'  => 'application/json',
//            'Authorization' => "Bearer $token",
//        ])
//            ->withoutVerifying()
//            ->post("$baseUrl/api/eWallet/v1/payments/otp", [
//                'encryptedPinCode' => $payload['pin_code'] ?? null,
//                'id' => 699806665,
//                'idType' => 'MSISDN',
//                'walletType' => 'INTERNATIONAL',
//            ])->json();
//
//        $request = Http::withHeaders([
//            'Content-Type'  => 'application/json',
//            'Authorization' => "Bearer $token",
//        ])
//            ->withoutVerifying()
//            ->post("$baseUrl/api/eWallet/v1/payments/onestep", [
//                'amount' => ['unit' => 'XOF', 'value' => 1],
//                'customer' => [
//                    'id' => 699806665,
//                    'idType' => 'MSISDN',
//                    'walletType' => 'INTERNATIONAL',
//                ],
//                'partner' => [
//                    'id' => 603214,
//                    'idType' => 'CODE',
//                ],
//                'reference' => $trxId
//            ])->json();

        dd($request);
        return PaymentProcess::updateOrCreate([
            'user_id'    => auth('sanctum')->id(),
            'model_type' => data_get($before, 'model_type'),
            'model_id'   => $modelId,
        ], [
            'id' => $trxId,
            'data' => array_merge([
                'id'         => $trxId,
                'url'        => $request['deepLink'],
                'payment_id' => $payment->id,
                'body'       => $request,
            ], $before)
        ]);
    }

    /**
     * @param string $baseUrl
     * @param array $payload
     * @return array
     * @throws Exception
     */
    public function getToken(string $baseUrl, array $payload): array
    {
        try {

            $getToken = Http::asForm()
                ->withoutVerifying()
                ->post("$baseUrl/oauth/token", [
                    'client_id'     => $payload['client_id'] ?? 'f8ef5452-9399-4bc2-8422-007f4f864dec',
                    'client_secret' => $payload['client_secret'] ?? '11cc577f-16da-49f6-ab59-b8e3c0f146c0',
                    'grant_type'    => $payload['grant_type'] ?? 'client_credentials',
                ])->json();

            if (!isset($getToken['access_token'])) {
                throw new Exception();
            }

            $getPublicKey = Http::withToken($getToken['access_token'])
                ->withoutVerifying()
                ->get("$baseUrl/api/account/v1/publicKeys")
                ->json();

            $payload['pin']      = $getPublicKey;
            $payload['pin_code'] = 'kSHHRpKI3rWvJaVVa4Tqn+Zkr1jN3L2oCohbSpCXJfbz6r0yZI3fSHQpz3KkBRE2tNRXAJekvBM1afsXrekxFbcdSgQzExUaFDeWBzUPvTrCBVg5R0NwqP6QdN5oAWN9xRKzCoekZ9eoFyJsgYzrUWf+PKJkYOWBw/s3PzCQjJZDBuMYQ338mK+51erudAo66S3JHL/YHDQWzWXI9CVe5ZceFba0MHOjkFh1QMhUy81wdmpVODqhWDVkdUIT4QOewOIxLMbswVQf50lufv6WupkR3FHnDcvUKaA4P5kiFdS6Q3kGTSiFKSAI11vbVodUqDsQDF397HPuY2Sj5v0G7Q==';
            $payload['token']    = $getToken;

        } catch (Throwable $e) {
            throw new Exception('403 error');
        }

        return $payload;
    }
}
