<?php

namespace App\Http\Controllers\API\v1\Dashboard\Seller;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\ShopWorkingDay\SellerRequest;
use App\Http\Resources\ShopResource;
use App\Http\Resources\ShopWorkingDayResource;
use App\Repositories\ShopWorkingDayRepository\ShopWorkingDayRepository;
use App\Services\ShopWorkingDayService\ShopWorkingDayService;
use Illuminate\Http\JsonResponse;

class ShopWorkingDayController extends SellerBaseController
{

    public function __construct(private ShopWorkingDayRepository $repository, private ShopWorkingDayService $service)
    {
        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        return $this->show();
    }

    /**
     * Display the specified resource.
     *
     * @param SellerRequest $request
     * @return JsonResponse
     */
    public function store(SellerRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['shop_id'] = $this->shop->id;

        $result = $this->service->create($validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(__('errors.' . ResponseError::NO_ERROR, locale: $this->language), []);
    }

    /**
     * Display the specified resource.
     * @return JsonResponse
     */
    public function show(): JsonResponse
    {
        $shopWorkingDays = $this->repository->show($this->shop->id);

        return $this->successResponse(__('errors.' . ResponseError::NO_ERROR, locale: $this->language), [
            'dates' => ShopWorkingDayResource::collection($shopWorkingDays),
            'shop'  => ShopResource::make($this->shop),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     * @param SellerRequest $request
     * @return JsonResponse
     */
    public function update(SellerRequest $request): JsonResponse
    {
        $result = $this->service->update($this->shop->id, $request->validated());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language),
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
        $this->service->delete($request->input('ids', []), $this->shop->id);

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_DELETED, locale: $this->language),
            []
        );
    }
}
