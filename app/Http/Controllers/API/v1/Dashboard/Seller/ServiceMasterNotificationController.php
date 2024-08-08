<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\Seller;

use App\Helpers\ResponseError;
use Illuminate\Http\JsonResponse;
use App\Models\ServiceMasterNotification;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Resources\ServiceMasterNotificationResource;
use App\Http\Requests\ServiceMasterNotification\SellerRequest;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use App\Services\ServiceMasterNotificationService\ServiceMasterNotificationService;
use App\Repositories\ServiceMasterNotificationRepository\ServiceMasterNotificationRepository;

class ServiceMasterNotificationController extends SellerBaseController
{
    public function __construct(
        private ServiceMasterNotificationRepository $repository,
        private ServiceMasterNotificationService $service
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
        $models = $this->repository->paginate($request->merge(['shop_id' => $this->shop->id])->all());

        return ServiceMasterNotificationResource::collection($models);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  SellerRequest  $request
     * @return JsonResponse
     */
    public function store(SellerRequest $request): JsonResponse
    {
        $result = $this->service->create($request->validated());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language),
            ServiceMasterNotificationResource::make(data_get($result, 'data'))
        );
    }

    /**
     * Display the specified resource.
     *
     * @param  ServiceMasterNotification $serviceMasterNotification
     * @return JsonResponse
     */
    public function show(ServiceMasterNotification $serviceMasterNotification): JsonResponse
    {
        if ($serviceMasterNotification->serviceMaster->shop_id !== $this->shop->id) {
            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_404,
                'message' => __('errors.' . ResponseError::ERROR_404, locale: $this->language),
            ]);
        }

        $model = $this->repository->show($serviceMasterNotification);

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            ServiceMasterNotificationResource::make($model)
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  ServiceMasterNotification $serviceMasterNotification
     * @param  SellerRequest $request
     * @return JsonResponse
     */
    public function update(ServiceMasterNotification $serviceMasterNotification, SellerRequest $request): JsonResponse
    {
        if ($serviceMasterNotification->serviceMaster->shop_id !== $this->shop->id) {
            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_404,
                'message' => __('errors.' . ResponseError::ERROR_404, locale: $this->language),
            ]);
        }

        $result = $this->service->update($serviceMasterNotification, $request->validated());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED, locale: $this->language),
            ServiceMasterNotificationResource::make(data_get($result, 'data'))
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  FilterParamsRequest $request
     * @return JsonResponse
     */
    public function destroy(FilterParamsRequest $request): JsonResponse
    {
        $this->service->delete($request->input('ids', []), $this->shop->id);
        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_DELETED, locale: $this->language), []
        );
    }
}
