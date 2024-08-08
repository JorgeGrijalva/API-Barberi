<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\Deliveryman;

use App\Helpers\ResponseError;
use App\Http\Requests\Order\SellerOrderReportRequest;
use App\Repositories\OrderRepository\DeliveryMan\OrderReportRepository;
use App\Traits\Notification;
use Illuminate\Http\JsonResponse;

class OrderReportController extends DeliverymanBaseController
{
    use Notification;

    /**
     * @param OrderReportRepository $repository
     */
    public function __construct(
        private OrderReportRepository $repository
    )
    {
        parent::__construct();
    }

    public function report(SellerOrderReportRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['deliveryman_id'] = auth('sanctum')->id();

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            $this->repository->report($validated)
        );
    }
}
