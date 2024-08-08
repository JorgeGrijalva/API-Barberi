<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\Seller;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\ShopClosedDate\SellerRequest;
use App\Http\Resources\ShopClosedDateResource;
use App\Http\Resources\ShopResource;
use App\Repositories\ShopClosedDateRepository\ShopClosedDateRepository;
use App\Services\ShopClosedDateService\ShopClosedDateService;
use Illuminate\Http\JsonResponse;

class ShopClosedDateController extends SellerBaseController
{

    public function __construct(private ShopClosedDateRepository $repository, private ShopClosedDateService $service)
    {
        parent::__construct();
    }

    /**
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
        $shopClosedDate = $this->repository->show($this->shop->id);

        return $this->successResponse(__('errors.' . ResponseError::NO_ERROR, locale: $this->language), [
            'closed_dates'  => ShopClosedDateResource::collection($shopClosedDate),
            'shop'          => ShopResource::make($this->shop),
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
        $this->service->delete($request->input('ids', []), $this->shop->id);

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_DELETED, locale: $this->language),
            []
        );
    }
}
