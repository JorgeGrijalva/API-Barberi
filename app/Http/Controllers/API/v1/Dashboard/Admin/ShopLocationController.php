<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\Admin;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\ShopLocation\AdminStoreRequest;
use App\Http\Resources\ShopLocationResource;
use App\Models\ShopLocation;
use App\Repositories\ShopLocationRepository\ShopLocationRepository;
use App\Services\ShopLocationService\ShopLocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ShopLocationController extends AdminBaseController
{

    public function __construct(private ShopLocationRepository $repository, private ShopLocationService $service)
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
        $shopLocations = $this->repository->paginate($request->all());

        return ShopLocationResource::collection($shopLocations);
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  AdminStoreRequest $request
     * @return JsonResponse
     */
    public function store(AdminStoreRequest $request): JsonResponse
    {
        $result = $this->service->create($request->validated());

        if (!data_get($result, 'data')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language),
            ShopLocationResource::make(data_get($result, 'data'))
        );
    }

    /**
     * Display the specified resource.
     *
     * @param  ShopLocation $shopLocation
     * @return JsonResponse
     */
    public function show(ShopLocation $shopLocation): JsonResponse
    {
        $shopLocation = $this->repository->show($shopLocation);

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            ShopLocationResource::make($shopLocation)
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  ShopLocation $shopLocation
     * @param  AdminStoreRequest $request
     * @return JsonResponse
     */
    public function update(ShopLocation $shopLocation, AdminStoreRequest $request): JsonResponse
    {
        $result = $this->service->update($shopLocation, $request->validated());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED, locale: $this->language),
            ShopLocationResource::make(data_get($result, 'data'))
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
        $this->service->destroy($request->input('ids', []));

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
