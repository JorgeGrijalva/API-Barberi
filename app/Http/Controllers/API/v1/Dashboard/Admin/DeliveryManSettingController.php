<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\Admin;

use App\Helpers\ResponseError;
use App\Http\Requests\DeliveryManSetting\AdminRequest;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Resources\DeliveryManSettingResource;
use App\Models\DeliveryManSetting;
use App\Models\User;
use App\Repositories\DeliveryManSettingRepository\DeliveryManSettingRepository;
use App\Services\DeliveryManSettingService\DeliveryManSettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DeliveryManSettingController extends AdminBaseController
{

    public function __construct(
        private DeliveryManSettingRepository $repository,
        private DeliveryManSettingService $service
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
    public function paginate(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $filter = $request->all();

        $deliveryMans = $this->repository->paginate($filter);

        return DeliveryManSettingResource::collection($deliveryMans);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param AdminRequest $request
     * @return JsonResponse
     */
    public function store(AdminRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $deliveryMan = User::find(data_get($validated, 'user_id'));

        if (!$deliveryMan->hasRole('deliveryman')) {
            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_400,
                'message' => 'You need change delivery man'
            ]);
        }

        $result = $this->service->create($validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language),
            DeliveryManSettingResource::make(data_get($result, 'data'))
        );
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $deliverymanSetting = $this->repository->detail($id);

        if (empty($deliverymanSetting)) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            DeliveryManSettingResource::make($deliverymanSetting)
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param int $id
     * @param AdminRequest $request
     * @return JsonResponse
     */
    public function update(int $id, AdminRequest $request): JsonResponse
    {
        $deliveryManSetting = DeliveryManSetting::find($id);

        if (empty($deliveryManSetting)) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        $validated = $request->validated();

        $deliveryMan = User::find(data_get($validated, 'user_id'));

        if (!$deliveryMan->hasRole('deliveryman')) {
            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_400,
                'message' => 'You need change delivery man'
            ]);
        }

        $result = $this->service->update($deliveryManSetting, $validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED, locale: $this->language),
            DeliveryManSettingResource::make(data_get($result, 'data'))
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function destroy(FilterParamsRequest $request): JsonResponse
    {
        $this->service->destroy($request->input('ids', []));

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_DELETED, locale: $this->language),
            []
        );
    }
}
