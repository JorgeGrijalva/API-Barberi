<?php

namespace App\Http\Controllers\API\v1\Dashboard\Payment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\PaymentRequest;
use App\Models\PaymentProcess;
use App\Services\CoreService;
use App\Services\PaymentService\BaseService;
use App\Services\PaymentService\StripeService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Throwable;

abstract class PaymentBaseController extends Controller
{
    use ApiResponse;

    public function __construct(private BaseService|StripeService|CoreService $service)
    {
        parent::__construct();
        $this->middleware(['sanctum.check'])->except(['created', 'resultTransaction', 'paymentWebHook']);
    }

    /**
     * process transaction.
     *
     * @param PaymentRequest $request
     * @return PaymentProcess|JsonResponse
     */
    public function processTransaction(PaymentRequest $request): PaymentProcess|JsonResponse
    {
        try {
            $data   = $this->service->getValidateData($request->validated());
            $result = $this->service->processTransaction($data);

            return $this->successResponse('success', $result);
        } catch (Throwable $e) {
            $this->error($e);
            return $this->onErrorResponse([
                'message' => $e->getMessage() . $e->getLine() . $e->getFile()
            ]);
        }

    }
}
