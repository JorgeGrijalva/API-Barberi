<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\Admin;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\DeliveryPointClosedDate\StoreRequest;
use App\Http\Resources\DeliveryPointClosedDateResource;
use App\Http\Resources\DeliveryPointResource;
use App\Models\DeliveryPoint;
use App\Repositories\DeliveryPointClosedDateRepository\DeliveryPointClosedDateRepository;
use App\Services\DeliveryPointClosedDateService\DeliveryPointClosedDateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DeliveryPointClosedDateController extends AdminBaseController
{

    public function __construct(
        private DeliveryPointClosedDateRepository $repository,
        private DeliveryPointClosedDateService $service
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
        $deliveryPointsWithClosedDays = $this->repository->paginate($request->all());

        return DeliveryPointResource::collection($deliveryPointsWithClosedDays);
    }

    /**
     * Display the specified resource.
     *
     * @param StoreRequest $request
     * @return JsonResponse
     */
    public function store(StoreRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->service->create($validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(__('errors.' . ResponseError::NO_ERROR, locale: $this->language), []);
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

        $shopClosedDate = $this->repository->show($deliveryPoint->id);

        return $this->successResponse(__('errors.' . ResponseError::NO_ERROR, locale: $this->language), [
            'closed_dates'   => DeliveryPointClosedDateResource::collection($shopClosedDate),
            'delivery_point' => DeliveryPointResource::make($deliveryPoint),
        ]);
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
        $deliveryPoint = DeliveryPoint::find($id);

        if (empty($deliveryPoint)) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        $result = $this->service->update($deliveryPoint->id, $request->validated());

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
