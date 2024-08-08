<?php

namespace App\Http\Controllers\API\v1\Dashboard\Payment;

use App\Services\PaymentService\MtnService;
use Illuminate\Http\Request;

class MtnController extends PaymentBaseController
{
    public function __construct(private MtnService $service)
    {
        parent::__construct($service);
    }

    /**
     * @param Request $request
     * @return array
     */
    public function paymentWebHook(Request $request): array
    {
        \Log::error('pa', $request->all());
//        return $this->service->afterHook();
    }

}
