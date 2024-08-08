<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\Seller;

use App\Helpers\ResponseError;
use App\Http\Requests\DeliveryPrice\StoreRequest;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Resources\DeliveryPriceResource;
use App\Models\DeliveryPrice;
use App\Repositories\DeliveryPriceRepository\DeliveryPriceRepository;
use App\Services\DeliveryPriceService\DeliveryPriceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DeliveryPriceController extends SellerBaseController
{
    public function __construct(
        private DeliveryPriceRepository $repository,
        private DeliveryPriceService $service
    )
    {
        parent::__construct();
    }

    public function index(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $model = $this->repository->paginate($request->merge(['shop_id' => $this->shop->id])->all());

        return DeliveryPriceResource::collection($model);
    }

    /**
     * Display the specified resource.
     *
     * @param DeliveryPrice $deliveryPrice
     * @return JsonResponse
     */
    public function show(DeliveryPrice $deliveryPrice): JsonResponse
    {
        if (!empty($deliveryPrice->shop_id) && $deliveryPrice->shop_id !== $this->shop->id) {
            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_404,
                'message' => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
            ]);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            DeliveryPriceResource::make($this->repository->show($deliveryPrice))
        );
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

        $exists = DeliveryPrice::where([
            'region_id'  => data_get($validated, 'region_id'),
            'country_id' => data_get($validated, 'country_id'),
            'city_id'    => data_get($validated, 'city_id'),
            'area_id'    => data_get($validated, 'area_id'),
            'shop_id'    => $this->shop->id,
        ])->exists();

        if ($exists) {
            return $this->onErrorResponse([
                'code' => ResponseError::ERROR_506,
                'message' => __('errors.' . ResponseError::ERROR_506, locale: $this->language)
            ]);
        }

        $result = $this->service->create($validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language),
            DeliveryPriceResource::make(data_get($result, 'data'))
        );
    }

    /**
     * Update a newly created resource in storage.
     *
     */
    public function update(DeliveryPrice $deliveryPrice, StoreRequest $request): JsonResponse
    {
        if ($deliveryPrice->shop_id !== $this->shop->id) {
            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_404,
                'message' => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
            ]);
        }

        $validated = $request->validated();

        $result = $this->service->update($deliveryPrice, $validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language),
            DeliveryPriceResource::make(data_get($result, 'data'))
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
        $result = $this->service->delete($request->input('ids', []), $this->shop->id);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse([
                'code'      => ResponseError::ERROR_404,
                'message'   => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
            ]);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_DELETED, locale: $this->language),
            []
        );
    }
}
