<?php
declare(strict_types=1);

namespace App\Services\SMSGatewayService;

use App\Models\SmsGateway;
use App\Services\CoreService;
use Illuminate\Support\Str;
use Throwable;
use Vonage\Client;
use Vonage\Client\Credentials\Basic;
use Vonage\SMS\Message\SMS;

class NexmoService extends CoreService
{

    protected function getModelClass(): string
    {
        return SmsGateway::class;
    }

    public function sendSms($gateway, $phone, $otp): array
    {

        if (!isset($gateway->api_key) || !isset($gateway->secret_key)) {
            return [
                'status' => false,
                'message' => 'Bad credentials. Contact with Support Team'
            ];
        }

        $basic  = new Basic($gateway->api_key, $gateway->secret_key);
        $client = new Client($basic);
        $text   = Str::replace('#OTP#', $otp['otpCode'], $gateway->text);

        try {
            $response = $client
                ->sms()
                ->send(new SMS($phone, $gateway->from, $text))
                ->current();

            $status = $response->getStatus();

            return ['status' => $status == 0, 'message' => $status];
        } catch (Throwable $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        }

    }
}
