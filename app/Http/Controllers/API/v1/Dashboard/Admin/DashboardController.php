<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\Admin;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Resources\StockResource;
use App\Models\User;
use App\Repositories\DashboardRepository\DashboardRepository;
use Artisan;
use DateTimeZone;
use Illuminate\Http\JsonResponse;

class DashboardController extends AdminBaseController
{

    public function __construct(private DashboardRepository $repository)
    {
        parent::__construct();
    }
    
    /**
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function ordersStatistics(FilterParamsRequest $request): JsonResponse
    {
        if (!in_array($request->input('time'), User::DATES)) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_422]);
        }

        $result = $this->repository->ordersStatistics($request->all());

        return $this->successResponse(__('errors.' . ResponseError::NO_ERROR, locale: $this->language), $result);
    }

    /**
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function ordersChart(FilterParamsRequest $request): JsonResponse
    {
        if (!in_array($request->input('time'), User::DATES)) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_422]);
        }

        $result = $this->repository->ordersChart($request->all());

        return $this->successResponse(__('errors.' . ResponseError::NO_ERROR, locale: $this->language), $result);
    }

    /**
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function productsStatistic(FilterParamsRequest $request): JsonResponse
    {
        if (!in_array($request->input('time'), User::DATES)) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_422]);
        }

        $result = $this->repository->productsStatistic($request->all());

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
            return $this->onErrorResponse(['code' => ResponseError::ERROR_422]);
        }

        $result = $this->repository->usersStatistic($request->all());

        return $this->successResponse(__('errors.' . ResponseError::NO_ERROR, locale: $this->language), $result);
    }

    /**
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function salesReport(FilterParamsRequest $request): JsonResponse
    {
        $result = $this->repository->salesReport($request->all());

        return $this->successResponse(__('errors.' . ResponseError::NO_ERROR, locale: $this->language), $result);
    }

    /**
     * @return JsonResponse
     */
    public function timeZones(): JsonResponse
    {
        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            DateTimeZone::listIdentifiers()
        );
    }

    /**
     * @return JsonResponse
     */
    public function timeZone(): JsonResponse
    {
        return $this->successResponse(__('errors.' . ResponseError::NO_ERROR, locale: $this->language), [
            'timeZone'  => config('app.timezone'),
            'time'      => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function timeZoneChange(FilterParamsRequest $request): JsonResponse
    {
        $timeZone = $request->input('timezone');
        $oldZone  = config('app.timezone');

        if (empty($timeZone) || !in_array($timeZone, DateTimeZone::listIdentifiers())) {
            $timeZone = $oldZone;
        }

        $path = base_path('config/app.php');

        file_put_contents(
            $path, str_replace(
                "'timezone' => '$oldZone',",
                "'timezone' => '$timeZone',",
                file_get_contents($path)
            )
        );

        Artisan::call('cache:clear');

        return $this->successResponse(__('errors.' . ResponseError::NO_ERROR, locale: $this->language), $timeZone);
    }
}
