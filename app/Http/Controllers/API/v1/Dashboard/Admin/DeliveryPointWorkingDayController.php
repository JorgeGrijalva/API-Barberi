<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\Admin;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\DeliveryPointWorkingDay\StoreRequest;
use App\Http\Resources\DeliveryPointResource;
use App\Http\Resources\DeliveryPointWorkingDayResource;
use App\Models\DeliveryPoint;
use App\Repositories\DeliveryPointWorkingDayRepository\DeliveryPointWorkingDayRepository;
use App\Services\DeliveryPointWorkingDayService\DeliveryPointWorkingDayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DeliveryPointWorkingDayController extends AdminBaseController
{

    public function __construct(
        private DeliveryPointWorkingDayRepository $repository,
        private DeliveryPointWorkingDayService $service
    )
    {
        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     *
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection
     */
    public function index(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $deliveryPointsWorkingDays = $this->repository->paginate($request->all());

        return DeliveryPointResource::collection($deliveryPointsWorkingDays);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $deliveryPoint = DeliveryPoint::find($id);

        if (empty($deliveryPoint)) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        $deliveryPointWorkingDays = $this->repository->show($deliveryPoint->id);

        return $this->successResponse(__('errors.' . ResponseError::NO_ERROR, locale: $this->language), [
            'dates'             => DeliveryPointWorkingDayResource::collection($deliveryPointWorkingDays),
            'delivery_point'    => DeliveryPointResource::make($deliveryPoint),
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param StoreRequest $request
     * @return JsonResponse
     */
    public function store(StoreRequest $request): JsonResponse
    {
        $result = $this->service->create($request->validated());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(__('errors.' . ResponseError::NO_ERROR, locale: $this->language), []);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param int $id
     * @param StoreRequest $request
     * @return JsonResponse
     */
    public function update(int $id, StoreRequest $request): JsonResponse
    {
        $result = $this->service->update($id, $request->validated());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED, locale: $this->language),
            []
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function changeDisabled(int $id): JsonResponse
    {
        $result = $this->service->changeDisabled($id);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED, locale: $this->language),
            []
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function destroy(FilterParamsRequest $request): JsonResponse
    {
        $this->service->delete($request->input('ids', []));

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_DELETED, locale: $this->language),
            []
        );
    }

    /**
     * @return JsonResponse
     */
    public function dropAll(): JsonResponse
    {
        $this->service->dropAll();

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_DELETED, locale: $this->language),
            []
        );
    }
}
