<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\Seller;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\ServiceExtra\StoreRequest;
use App\Http\Resources\ServiceExtraResource;
use App\Models\Service;
use App\Models\ServiceExtra;
use App\Repositories\ServiceExtraRepository\ServiceExtraRepository;
use App\Services\ServiceExtraService\ServiceExtraService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ServiceExtraController extends SellerBaseController
{
    public function __construct(
        protected ServiceExtraService $service,
        protected ServiceExtraRepository $repository
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

        return ServiceExtraResource::collection($models);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  StoreRequest $request
     * @return JsonResponse
     */
    public function store(StoreRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $shopId    = Service::where('id', $validated['service_id'])->value('shop_id');

        if (!empty($shopId) && $shopId !== $this->shop->id) {
            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_404,
                'message' => __('errors.' . ResponseError::SERVICE_DOES_NOT_BELONG_TO_THIS_SHOP, locale: $this->language),
            ]);
        }

        $validated['shop_id'] = $shopId;
        $result = $this->service->create($validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language),
            ServiceExtraResource::make(data_get($result, 'data'))
        );
    }

    /**
     * Display the specified resource.
     *
     * @param  ServiceExtra $serviceExtra
     * @return JsonResponse
     */
    public function show(ServiceExtra $serviceExtra): JsonResponse
    {
        if (!empty($serviceExtra->shop_id) && $serviceExtra->shop_id !== $this->shop->id) {
            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_404,
                'message' => __('errors.' . ResponseError::ERROR_404, locale: $this->language),
            ]);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            ServiceExtraResource::make($this->repository->show($serviceExtra))
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  ServiceExtra $serviceExtra
     * @param  StoreRequest $request
     * @return JsonResponse
     */
    public function update(ServiceExtra $serviceExtra, StoreRequest $request): JsonResponse
    {
        if (!empty($serviceExtra->shop_id) && $serviceExtra->shop_id !== $this->shop->id) {
            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_404,
                'message' => __('errors.' . ResponseError::ERROR_404, locale: $this->language),
            ]);
        }

        $validated = $request->validated();
        $validated['shop_id'] = $this->shop->id;

        $result = $this->service->update($serviceExtra, $validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED, locale: $this->language),
            ServiceExtraResource::make(data_get($result, 'data'))
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
        $this->service->delete($request->input('ids', []), ['shop_id' => $this->shop->id]);

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_DELETED, locale: $this->language),
            []
        );
    }
}
