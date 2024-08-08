<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\Seller;

use App\Helpers\ResponseError;
use App\Helpers\Utility;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\ServiceMaster\AdminStoreRequest;
use App\Http\Requests\ServiceMaster\UpdateRequest;
use App\Http\Resources\ServiceMasterResource;
use App\Models\ServiceMaster;
use App\Repositories\ServiceMasterRepository\ServiceMasterRepository;
use App\Services\ServiceMasterService\ServiceMasterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ServiceMasterController extends SellerBaseController
{
    public function __construct(private ServiceMasterRepository $repository, private ServiceMasterService $service)
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

        return ServiceMasterResource::collection($models);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param AdminStoreRequest $request
     * @return JsonResponse
     */
    public function store(AdminStoreRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['shop_id'] = $this->shop->id;

        $user = Utility::checkUserInvitationByRole((int)$request->input('master_id'), 'master', $this->shop->id);

        if (!$user) {
            return $this->onErrorResponse([
                'status'  => false,
                'message' => __('errors.' . ResponseError::SELECTED_MASTER_INVALID, locale: $this->language),
                'code'    => ResponseError::ERROR_501
            ]);
        }

        $result = $this->service->create($validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language),
            ServiceMasterResource::make(data_get($result, 'data'))
        );
    }

    /**
     * Display the specified resource.
     *
     * @param ServiceMaster $serviceMaster
     * @return JsonResponse
     */
    public function show(ServiceMaster $serviceMaster): JsonResponse
    {
        if ($serviceMaster->shop_id !== $this->shop->id) {
            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_404,
                'message' => __('errors.' . ResponseError::ERROR_404, locale: $this->language),
            ]);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            ServiceMasterResource::make($this->repository->show($serviceMaster))
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param ServiceMaster $serviceMaster
     * @param UpdateRequest $request
     * @return JsonResponse
     */
    public function update(ServiceMaster $serviceMaster, UpdateRequest $request): JsonResponse
    {
        if ($serviceMaster->shop_id !== $this->shop->id) {
            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_404,
                'message' => __('errors.' . ResponseError::ERROR_404, locale: $this->language),
            ]);
        }

        $validated = $request->validated();
        $user      = Utility::checkUserInvitationByRole($serviceMaster->master_id, 'master', $this->shop->id);

        if (!$user) {
            return $this->onErrorResponse([
                'status'  => false,
                'message' => __('errors.' . ResponseError::SELECTED_MASTER_INVALID, locale: $this->language),
                'code'    => ResponseError::ERROR_501
            ]);
        }

        $result = $this->service->update($serviceMaster, $validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED, locale: $this->language),
            ServiceMasterResource::make(data_get($result, 'data'))
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
        $result = $this->service->delete($request->merge(['shop_id' => $this->shop->id])->all());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_DELETED, locale: $this->language),
            []
        );
    }
}
