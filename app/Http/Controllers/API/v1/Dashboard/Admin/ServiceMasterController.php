<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\Admin;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\ServiceMaster\AdminStoreRequest;
use App\Http\Requests\ServiceMaster\UpdateRequest;
use App\Http\Resources\ServiceMasterResource;
use App\Models\ServiceMaster;
use App\Repositories\ServiceMasterRepository\ServiceMasterRepository;
use App\Services\ServiceMasterService\ServiceMasterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ServiceMasterController extends AdminBaseController
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
        $models = $this->repository->paginate($request->all());

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
        $validated = $request->validated();

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
        $result = $this->service->delete($request->all());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_DELETED, locale: $this->language),
            []
        );
    }
}
