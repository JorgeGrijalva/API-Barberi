<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\Seller;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Resources\StockResource;
use App\Models\User;
use App\Repositories\DashboardRepository\DashboardRepository;
use Illuminate\Http\JsonResponse;

class DashboardController extends SellerBaseController
{

    /**
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function ordersStatistics(FilterParamsRequest $request): JsonResponse
    {
        if (!in_array($request->input('time'), User::DATES)) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_400]);
        }

        $result = (new DashboardRepository)->ordersStatistics(
            $request->merge(['shop_id' => $this->shop->id])->all()
        );

        return $this->successResponse(__('errors.' . ResponseError::NO_ERROR, locale: $this->language), $result);
    }

    /**
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function ordersChart(FilterParamsRequest $request): JsonResponse
    {
        if (!in_array($request->input('time'), User::DATES)) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_400]);
        }

        $result = (new DashboardRepository)->ordersChart(
            $request->merge(['shop_id' => $this->shop->id])->all()
        );

        return $this->successResponse(__('errors.' . ResponseError::NO_ERROR, locale: $this->language), $result);
    }

    /**
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function productsStatistic(FilterParamsRequest $request): JsonResponse
    {
        if (!in_array($request->input('time'), User::DATES)) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_400]);
        }

        $result = (new DashboardRepository)->productsStatistic(
            $request->merge(['shop_id' => $this->shop->id])->all()
        );

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            StockResource::collection($result)
        );
    }

    /**
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function usersStatistic(FilterParamsRequest $request): JsonResponse
    {
        if (!in_array($request->input('time'), User::DATES)) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_400]);
        }

        $result = (new DashboardRepository)->usersStatistic(
            $request->merge(['shop_id' => $this->shop->id])->all()
        );

        return $this->successResponse(__('errors.' . ResponseError::NO_ERROR, locale: $this->language), $result);
    }

    /**
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function salesReport(FilterParamsRequest $request): JsonResponse
    {
        $result = (new DashboardRepository)->salesReport($request->merge(['shop_id' => $this->shop->id])->all());

        return $this->successResponse(__('errors.' . ResponseError::NO_ERROR, locale: $this->language), $result);
    }
}
