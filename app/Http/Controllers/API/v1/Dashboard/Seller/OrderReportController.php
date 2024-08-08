<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\Seller;

use App\Helpers\ResponseError;
use App\Http\Requests\Order\OrderChartPaginateRequest;
use App\Http\Requests\Order\OrderChartRequest;
use App\Http\Requests\Order\OrderTransactionRequest;
use App\Repositories\OrderRepository\OrderReportRepository;
use App\Traits\Notification;
use Illuminate\Http\JsonResponse;
use Throwable;

class OrderReportController extends SellerBaseController
{
    use Notification;

    public function __construct(private OrderReportRepository $repository)
    {
        parent::__construct();
    }

    public function reportChart(OrderChartRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $validated['shop_id'] = $this->shop->id;

            $result = $this->repository->ordersReportChart($validated);

            return $this->successResponse('Successfully', $result);
        } catch (Throwable $e) {

            $this->error($e);

            return $this->onErrorResponse(['code' => ResponseError::ERROR_400, 'message' => $e->getMessage() . $e->getLine()]);
        }
    }

    public function reportTransactions(OrderTransactionRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $validated['shop_id'] = $this->shop->id;

            $result = $this->repository->orderReportTransaction($validated);

            return $this->successResponse('Successfully', $result);
        } catch (Throwable $e) {

            $this->error($e);

            return $this->onErrorResponse(['code' => ResponseError::ERROR_400, 'message' => $e->getMessage()]);
        }
    }

    public function reportChartPaginate(OrderChartPaginateRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $validated['shop_id'] = $this->shop->id;

            $result = $this->repository->ordersReportChartPaginate($validated);

            return $this->successResponse('Successfully data', $result);
        } catch (Throwable $e) {

            $this->error($e);

            return $this->onErrorResponse(['code' => ResponseError::ERROR_400, 'message' => $e->getMessage()]);
        }
    }

}
