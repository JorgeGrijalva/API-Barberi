<?php

namespace App\Http\Controllers\API\v1\Dashboard\Payment;

use App\Http\Requests\Payment\PaymentRequest;
use App\Models\PaymentProcess;
use App\Models\WalletHistory;
use App\Services\PaymentService\IyzicoService;
use App\Traits\ApiResponse;
use App\Traits\OnResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Log;
use Throwable;

class IyzicoController extends PaymentBaseController
{
    use OnResponse, ApiResponse;

    public function __construct(private IyzicoService $service)
    {
        parent::__construct($service);
    }

    public function processTransaction(PaymentRequest $request): PaymentProcess|JsonResponse
    {
        try {
            return $this->service->processTransaction($request->all());
        } catch (Throwable $e) {
            $this->error($e);
            return $this->onErrorResponse([
                'message' => $e->getMessage(),
                'code'    => (string)$e->getCode()
            ]);
        }

    }

    /**
     * @param Request $request
     * @return array
     */
    public function paymentWebHook(Request $request): array
    {
        Log::error('paymentWebHook', $request->all());
        $status = $request->input('data.object.status');

        $status = match ($status) {
            'succeeded' => WalletHistory::PAID,
            default     => 'progress',
        };

        $token = $request->input('data.object.id');

        return $this->service->afterHook($token, $status);
    }

}
