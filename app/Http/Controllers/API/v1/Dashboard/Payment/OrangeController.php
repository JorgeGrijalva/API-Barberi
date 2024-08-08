<?php

namespace App\Http\Controllers\API\v1\Dashboard\Payment;

use App\Services\PaymentService\OrangeService;
use Illuminate\Http\Request;

class OrangeController extends PaymentBaseController
{
    public function __construct(private OrangeService $service)
    {
        parent::__construct($service);
    }

    /**
     * @param Request $request
     * @return array
     */
    public function paymentWebHook(Request $request): array
    {
        \Log::error('req', $request->all());
//        return $this->service->afterHook();
    }

}
