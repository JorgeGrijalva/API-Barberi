<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\Admin;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\DeliveryPoint\StoreRequest;
use App\Http\Resources\DeliveryPointResource;
use App\Models\DeliveryPoint;
use App\Repositories\DeliveryPointRepository\DeliveryPointRepository;
use App\Services\DeliveryPointService\DeliveryPointService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DeliveryPointController extends AdminBaseController
{

    public function __construct(private DeliveryPointRepository $repository, private DeliveryPointService $service)
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
        $deliveryPoints = $this->repository->paginate($request->all());

        return DeliveryPointResource::collection($deliveryPoints);
    }

    /**
     * Display the specified resource.
     *
     * @param DeliveryPoint $deliveryPoint
     * @return JsonResponse
     */
    public function show(DeliveryPoint $deliveryPoint): JsonResponse
    {
        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            DeliveryPointResource::make($this->repository->show($deliveryPoint))
        );
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

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            DeliveryPointResource::make($this->repository->show(data_get($result, 'data')))
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param DeliveryPoint $deliveryPoint
     * @param StoreRequest $request
     * @return JsonResponse
     */
    public function update(DeliveryPoint $deliveryPoint, StoreRequest $request): JsonResponse
    {
        $result = $this->service->update($deliveryPoint, $request->validated());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED, locale: $this->language),
            DeliveryPointResource::make($this->repository->show(data_get($result, 'data')))
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function changeActive(int $id): JsonResponse
    {
        $result = $this->service->changeActive($id);

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
