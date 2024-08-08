<?php

namespace App\Http\Controllers\API\v1\Dashboard\Payment;

use App\Models\WalletHistory;
use App\Services\PaymentService\RazorPayService;
use Illuminate\Http\Request;

class RazorPayController extends PaymentBaseController
{
    public function __construct(private RazorPayService $service)
    {
        parent::__construct($service);
    }

    /**
     * @param Request $request
     * @return void
     */
    public function paymentWebHook(Request $request): void
    {
        $token  = $request->input('payload.payment_link.entity.id');
        $status = $request->input('payload.payment_link.entity.status');

        $status = match ($status) {
            'cancelled', 'expired'  => WalletHistory::CANCELED,
            'paid'                  => WalletHistory::PAID,
            default                 => 'progress',
        };

        $this->service->afterHook($token, $status);
    }
}
