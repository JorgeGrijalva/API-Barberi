<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\Seller;

use App\Helpers\ResponseError;
use App\Http\Requests\Discount\SellerRequest;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Resources\DiscountResource;
use App\Models\Discount;
use App\Repositories\DiscountRepository\DiscountRepository;
use App\Services\DiscountService\DiscountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DiscountController extends SellerBaseController
{
    
    public function __construct(private DiscountRepository $repository, private DiscountService $service)
    {
        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     *
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection|JsonResponse
     */
    public function paginate(FilterParamsRequest $request): JsonResponse|AnonymousResourceCollection
    {
        $discounts = $this->repository->discountsPaginate(
            $request->merge(['shop_id' => $this->shop->id])->all()
        );

        return DiscountResource::collection($discounts);
    }

    /**
     * Store a newly created resource in storage.
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

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language),
            DiscountResource::make(data_get($result, 'data'))
        );
    }

    /**
     * Display the specified resource.
     *
     * @param Discount $discount
     * @return JsonResponse
     */
    public function show(Discount $discount): JsonResponse
    {
        if ($discount->shop_id !== $this->shop->id) {
            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_104,
                'message' => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
            ]);
        }

        $discount = $this->repository->discountDetails($discount);

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            DiscountResource::make($discount)
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Discount $discount
     * @param SellerRequest $request
     * @return JsonResponse
     */
    public function update(Discount $discount, SellerRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['shop_id'] = $this->shop->id;

        $result = $this->service->update($discount, $validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED, locale: $this->language),
            DiscountResource::make(data_get($result, 'data'))
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
        $this->service->delete($request->input('ids'), $this->shop->id);

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_DELETED, locale: $this->language),
            []
        );
    }

    public function setActiveStatus($id): JsonResponse
    {
        $discount = Discount::firstWhere(['id' => $id, 'shop_id' => $this->shop->id]);

        if (!$discount) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        $discount->update(['active' => !$discount->active]);

        $discount->stocks()->update([
            'discount_expired_at' => null
        ]);

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language), 
            DiscountResource::make($discount)
        );
    }
}
