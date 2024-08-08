<?php

namespace App\Http\Controllers\API\v1\Dashboard\Payment;

use App\Services\PaymentService\PayStackService;
use Illuminate\Http\Request;

class PayStackController extends PaymentBaseController
{
    public function __construct(private PayStackService $service)
    {
        parent::__construct($service);
    }

    /**
     * @param Request $request
     * @return void
     */
    public function paymentWebHook(Request $request): void
    {
        $status = $request->input('event');

        $status = match ($status) {
            'charge.success'    => 'paid',
            default             => 'progress',
        };

        $token = request()->input('data.reference');

        $this->service->afterHook($token, $status);
    }

}
