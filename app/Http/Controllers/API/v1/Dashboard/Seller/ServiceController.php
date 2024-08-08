<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\Seller;

use App\Helpers\ResponseError;
use App\Http\Requests\Service\ExtraUpdateRequest;
use App\Http\Requests\Service\FaqsUpdateRequest;
use App\Http\Requests\Service\StoreRequest;
use App\Http\Requests\Service\UpdateRequest;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use App\Repositories\ServiceRepository\ServiceRepository;
use App\Services\ModelService\ModelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ServiceController extends SellerBaseController
{
    public function __construct(private ServiceRepository $repository, private ModelService $service)
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

        return ServiceResource::collection($models);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreRequest $request
     * @return JsonResponse
     */
    public function store(StoreRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['shop_id'] = $this->shop->id;
        unset($validated['commission_fee']);
        $result = $this->service->create($validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language),
            ServiceResource::make(data_get($result, 'data'))
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param int $id
     * @param ExtraUpdateRequest $request
     * @return JsonResponse
     */
    public function extrasUpdate(int $id, ExtraUpdateRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->service->extrasUpdate($id, $validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language),
            ServiceResource::make(data_get($result, 'data'))
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param int $id
     * @param FaqsUpdateRequest $request
     * @return JsonResponse
     */
    public function faqsUpdate(int $id, FaqsUpdateRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->service->faqsUpdate($id, $validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language),
            ServiceResource::make(data_get($result, 'data'))
        );
    }

    /**
     * Display the specified resource.
     *
     * @param Service $service
     * @return JsonResponse
     */
    public function show(Service $service): JsonResponse
    {
        if ($service->shop_id !== $this->shop->id) {
            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_404,
                'message' => __('errors.' . ResponseError::ERROR_404, locale: $this->language),
            ]);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            ServiceResource::make($this->repository->show($service))
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Service $service
     * @param UpdateRequest $request
     * @return JsonResponse
     */
    public function update(Service $service, UpdateRequest $request): JsonResponse
    {
        if ($service->shop_id !== $this->shop->id) {
            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_404,
                'message' => __('errors.' . ResponseError::ERROR_404, locale: $this->language),
            ]);
        }

        $validated = $request->validated();
        unset($validated['commission_fee']);
        $result = $this->service->update($service, $validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED, locale: $this->language),
            ServiceResource::make(data_get($result, 'data'))
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
