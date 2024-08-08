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

class MtnService extends BaseService
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
            ->where('tag', Payment::TAG_MTN)
            ->first();

        $payload = $payment?->paymentPayload?->payload ?? [];

        $token = $payload['token']['access_token'] ?? 'F22ru2kw6pgB1wmwMckFiZ3yH23V';

        $baseUrl = $payload['url'] ?? 'https://api.mtn.com'; // https://api.orange-sonatel.com

        if (empty($token)) {
            $payload = $this->getToken($baseUrl, $payload);
            $token   = $payload['token']['access_token'];
            $payment?->paymentPayload()?->updateOrCreate(['payload' => $payload]);
        }

        [$key, $before] = $this->getPayload($data, $payload);

        $host = request()->getSchemeAndHttpHost();

        $modelId = data_get($before, 'model_id');
        $trxId = Str::uuid()->toString();

        $a = [
            "refresh_token_expires_in" => "0",
            "api_product_list" => "[Payments V1, MoMo_Verification, Withdrawals V1]",
            "api_product_list_json" => [
                  0 => "Payments V1",
              1 => "MoMo_Verification",
              2 => "Withdrawals V1",
            ],
            "organization_name" => "mtn-prod",
            "developer.email" => "fameni@beprosoft.com",
            "token_type" => "BearerToken",
            "issued_at" => "1714475259055",
            "client_id" => "gWG4AkGIkCvHGkQqRFL8id1Q0sjbGiul",
            "access_token" => "hIaqbgxEe24UjEI92mp0bxDqVHbr",
            "application_name" => "58330ae9-bf7d-4f9f-80a8-75c3ce30b2ed",
            "scope" => "",
            "expires_in" => "3599",
            "refresh_count" => "0",
            "status" => "approved",
        ];

        $request = Http::withHeaders([
            'Authorization' => "Bearer $token",
        ])->post("$baseUrl/v1/payments?grant_type=client_credentials", [
            'channel' => 'Koiffure',
            'correlatorId' => $trxId,
            'quoteId' => auth('sanctum')->id(),
            'transactionType' => 'Payment',
            'callbackURL' => "$host/api/v1/webhook/mtn/payment",
            'amount' => [
                'amount' => data_get($before, 'total_price'),
                'units' => 'XOF',
            ],
            'totalAmount' => [
                'amount' => data_get($before, 'total_price'),
                'units' => 'XOF',
            ],
            'payer' => [
                'payerIdType' => 'MSISDN',
                'payerId' => auth('sanctum')->id(),
                'payerNote' => 'Manual Boost for RWC',
                'payerName' => auth('sanctum')->user()->firstname ?? 'firstname',
                'payerRef' => auth('sanctum')->user()->uuid,
                'payerEmail' => auth('sanctum')->user()->email ?? Str::random(16) . '@gmail.com',
                'payerSurname' => auth('sanctum')->user()->lastname ?? 'firstname',
                'includePayerCharges' => false,
            ],
            'payee' => [
                'amount' => [
                    'amount' => data_get($before, 'total_price'),
                    'units' => 'XOF',
                ],
                'totalAmount' => [
                    'amount' => data_get($before, 'total_price'),
                    'units' => 'XOF',
                ],
                'payeeIdType' => 'MSISDN',
                'payerId' => auth('sanctum')->id(),
                'payerNote' => 'Manual Boost for RWC',
                'payerName' => auth('sanctum')->user()->firstname ?? 'firstname',
            ],
            'paymentMethod' => [
                'type' => 'Mobile Money',
            ],
        ]);
//        $request = Http::withHeaders([
//            'Content-Type' => 'application/json',
//            'Authorization' => "Bearer $token",
//            'transactionId' => $trxId,
//        ])->post("https://api.mtn.com/v1/payments", [
//            'channel' => 'Facebook',
//            'quoteId' => 9223372036854775807,
//            'description' => "Manual Boost for RW",
//            'authenticationType' => "Query Payment",
//            'callbackUrl' => "$host/api/v1/webhook/mtn/payment",
//            'redirectUrl' => "$host/payment-success?$key=$modelId",
//            'deliveryMethod' => "Paylink",
//            'payer' => [
//                'payerIdType' => 'MSISDN',
//                'payerId' => auth('sanctum')->id(),
//                'payerNote' => 'Manual Boost for RWC',
//                'payerName' => auth('sanctum')->user()->firstname ?? 'firstname',
//                'payerRef' => auth('sanctum')->user()->uuid,
//                'payerEmail' => auth('sanctum')->user()->email ?? Str::random(16) . '@gmail.com',
//                'payerSurname' => auth('sanctum')->user()->lastname ?? 'firstname',
//                'includePayerCharges' => false,
//            ],
//            'paymentmethods' => [
//                "Card Payment"
//            ],
//            'totalAmount' => [
//                'amount' => data_get($before, 'total_price'),
//                'units' => 'XOF',
//            ],
//            'itemDetails' => [
//                [
//                    'itemName' => 'ITEM_PURCHASE',
//                    'itemDescription' => 'booking products',
//                    'itemValue' => data_get($before, 'total_price'),
//                    'currency' => 'ZAR',
//                    'quantity' => 1,
//                    'AdditionalInformation' => [
//                        'name' => 'BundleName',
//                        'description' => 'Voice_1111',
//                    ],
//                ]
//            ],
//        ]);

        dd($request->body(), " $token", $request->status());
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
                'body'       => $request->body(),
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
                ->post("$baseUrl/v1/oauth/access_token?grant_type=client_credentials", [
                    'client_id'     => $payload['client_id'] ?? 'SYzd4DDFPWdk3Q9Q5SQJkjcIiEqZNQnF',
                    'client_secret' => $payload['client_secret'] ?? 'QQ01nsEzG63y4opT',
                ])->json();

            if (!isset($getToken['access_token'])) {
                throw new Exception();
            }

            $payload['token'] = $getToken;
        } catch (Throwable $e) {
            throw new Exception('403 error');
        }

        return $payload;
    }
}
