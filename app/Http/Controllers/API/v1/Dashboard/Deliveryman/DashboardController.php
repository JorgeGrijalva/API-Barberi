<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\Deliveryman;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Repositories\DashboardRepository\DashboardRepository;
use Illuminate\Http\JsonResponse;

class DashboardController extends DeliverymanBaseController
{
    /**
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function countStatistics(FilterParamsRequest $request): JsonResponse
    {
        $filter = $request->merge(['deliveryman_id' => auth('sanctum')->id()])->all();

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            (new DashboardRepository)->orderByStatusStatistics($filter)
        );
    }
}
