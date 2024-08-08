<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\Admin;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\Order\OrderChartPaginateRequest;
use App\Http\Requests\Order\OrderChartRequest;
use App\Http\Requests\Order\OrderTransactionRequest;
use App\Http\Requests\Order\StocksCalculateRequest;
use App\Repositories\OrderRepository\OrderReportRepository;
use Illuminate\Http\JsonResponse;
use Throwable;

class OrderReportController extends AdminBaseController
{
    public function __construct(
        private OrderReportRepository $repository,
    )
    {
        parent::__construct();
    }

    /**
     * Calculate products when cart updated.
     *
     * @param StocksCalculateRequest $request
     * @return JsonResponse
     */
    public function orderStocksCalculate(StocksCalculateRequest $request): JsonResponse
    {
        $result = $this->repository->orderStocksCalculate($request->validated());

        return $this->successResponse(__('errors.' . ResponseError::NO_ERROR, locale: $this->language), $result);
    }

    /**
     * @param OrderChartRequest $request
     * @return JsonResponse
     */
    public function reportChart(OrderChartRequest $request): JsonResponse
    {
        try {
            $result = $this->repository->ordersReportChart($request->validated());

            return $this->successResponse('Successfully imported', $result);
        } catch (Throwable $e) {

            $this->error($e);

            return $this->onErrorResponse(['code' => ResponseError::ERROR_400, 'message' => $e->getMessage()]);
        }
    }

    /**
     * @param OrderChartPaginateRequest $request
     * @return JsonResponse
     */
    public function reportChartPaginate(OrderChartPaginateRequest $request): JsonResponse
    {
        try {
            $result = $this->repository->ordersReportChartPaginate($request->validated());

            return $this->successResponse('Successfully data', $result);
        } catch (Throwable $e) {

            $this->error($e);

            return $this->onErrorResponse(['code' => ResponseError::ERROR_400, 'message' => $e->getMessage()]);
        }
    }

    /**
     * @param OrderTransactionRequest $request
     * @return JsonResponse
     */
    public function reportTransactions(OrderTransactionRequest $request): JsonResponse
    {
        try {
            $result = $this->repository->orderReportTransaction($request->validated());

            return $this->successResponse('Successfully', $result);
        } catch (Throwable $e) {

            $this->error($e);

            return $this->onErrorResponse(['code' => ResponseError::ERROR_400, 'message' => $e->getMessage()]);
        }
    }

    /**
     * @param OrderChartPaginateRequest $request
     * @return JsonResponse
     */
    public function revenueReport(OrderChartPaginateRequest $request): JsonResponse
    {
        try {
            $result = $this->repository->revenueReport($request->validated());

            return $this->successResponse('Successfully data', $result);
        } catch (Throwable $e) {

            $this->error($e);

            return $this->onErrorResponse(['code' => ResponseError::ERROR_400, 'message' => $e->getMessage() . $e->getLine()]);
        }
    }

    /**
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function overviewCarts(FilterParamsRequest $request): JsonResponse
    {
        try {
            $result = $this->repository->overviewCarts($request->validated());

            return $this->successResponse('Successfully data', $result);
        } catch (Throwable $e) {

            $this->error($e);

            return $this->onErrorResponse(['code' => ResponseError::ERROR_400, 'message' => $e->getMessage()]);
        }
    }

    /**
     * @param OrderChartPaginateRequest $request
     * @return JsonResponse
     */
    public function overviewProducts(OrderChartPaginateRequest $request): JsonResponse
    {
        try {
            $result = $this->repository->overviewProducts($request->validated());

            return $this->successResponse('Successfully data', $result);
        } catch (Throwable $e) {

            $this->error($e);

            return $this->onErrorResponse(['code' => ResponseError::ERROR_400, 'message' => $e->getMessage()]);
        }
    }

    /**
     * @param OrderChartPaginateRequest $request
     * @return JsonResponse
     */
    public function overviewCategories(OrderChartPaginateRequest $request): JsonResponse
    {
        try {
            $result = $this->repository->overviewCategories($request->validated());

            return $this->successResponse('Successfully data', $result);
        } catch (Throwable $e) {

            $this->error($e);

            return $this->onErrorResponse(['code' => ResponseError::ERROR_400, 'message' => $e->getMessage()]);
        }
    }

}
