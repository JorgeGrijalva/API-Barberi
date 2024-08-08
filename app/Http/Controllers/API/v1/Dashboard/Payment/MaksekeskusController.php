<?php

namespace App\Http\Controllers\API\v1\Dashboard\Payment;

use App\Models\Transaction;
use App\Services\PaymentService\MaksekeskusService;
use Illuminate\Http\Request;

class MaksekeskusController extends PaymentBaseController
{
    public function __construct(private MaksekeskusService $service)
    {
        parent::__construct($service);
    }

    /**
     * @param Request $request
     * @return array
     */
    public function paymentWebHook(Request $request): array
    {
        $encode = @json_decode($request->input('json'));

        $status = $request->input('json.status');

        if (@data_get($encode, 'status')) {
            $status = data_get($encode, 'status');
        }

        $status = match ($status) {
            'COMPLETED' => Transaction::STATUS_PAID,
            'CANCELLED', 'EXPIRED' => Transaction::STATUS_CANCELED,
            default     => Transaction::STATUS_PROGRESS,
        };

        $token = $request->input('json.transaction');

        if (@data_get($encode, 'transaction')) {
            $token = data_get($encode, 'transaction');
        }

        return $this->service->afterHook($token, $status);
    }

}
