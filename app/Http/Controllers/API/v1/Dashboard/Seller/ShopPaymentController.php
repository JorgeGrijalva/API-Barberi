<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\Seller;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\ShopPayment\StoreRequest;
use App\Http\Requests\ShopPayment\UpdateRequest;
use App\Http\Resources\PaymentResource;
use App\Http\Resources\ShopPaymentResource;
use App\Models\ShopPayment;
use App\Repositories\ShopPaymentRepository\ShopPaymentRepository;
use App\Services\ShopServices\ShopPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ShopPaymentController extends SellerBaseController
{

    public function __construct(private ShopPaymentRepository $repository, private ShopPaymentService $service)
    {
        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     *
     * @param FilterParamsRequest $request
     * @return JsonResponse|AnonymousResourceCollection
     */
    public function index(FilterParamsRequest $request): JsonResponse|AnonymousResourceCollection
    {
        $payments = $this->repository->list($request->merge(['shop_id' => $this->shop->id])->all());

        return ShopPaymentResource::collection($payments);
    }

    /**
     * Display a listing of the resource.
     *
     * @param StoreRequest $request
     * @return JsonResponse
     */
    public function store(StoreRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['shop_id'] = $this->shop->id;

        $result = $this->service->create($validated);

        if (!data_get($result,'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED, locale: $this->language),
            []
        );
    }

    public function shopNonExist(): JsonResponse
    {
        $payment = $this->repository->shopNonExist($this->shop->id);

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED, locale: $this->language),
            PaymentResource::collection($payment)
        );
    }

    /**
     * Display the specified resource.
     *
     * @param ShopPayment $shopPayment
     * @return JsonResponse
     */
    public function show(ShopPayment $shopPayment): JsonResponse
    {
        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            ShopPaymentResource::make($this->repository->show($shopPayment))
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateRequest $request
     * @param ShopPayment $shopPayment
     * @return JsonResponse
     */
    public function update(ShopPayment $shopPayment, UpdateRequest $request): JsonResponse
    {
        $result = $this->service->update($request->validated(), $shopPayment);

        if (!data_get($result,'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED, locale: $this->language),
            []
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function destroy(FilterParamsRequest $request): JsonResponse
    {
        $result = $this->service->delete($request->input('ids', []), $this->shop->id);

        if (!data_get($result,'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_DELETED, locale: $this->language),
            []
        );
    }

    /**
     * Set Model Active.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function setActive(int $id): JsonResponse
    {
        $result = $this->service->setActive($id, $this->shop->id);

        if (!data_get($result,'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED, locale: $this->language),
            ShopPaymentResource::make(data_get($result, 'data'))
        );
    }
}
