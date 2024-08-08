<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\Admin;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\Shop\SubscriptionRequest;
use App\Http\Resources\ShopSubscriptionResource;
use App\Models\ShopSubscription;
use App\Models\Subscription;
use App\Repositories\ShopSubscriptionRepository\ShopSubscriptionRepository;
use App\Services\ShopSubscriptionService\ShopSubscriptionService;
use App\Services\SubscriptionService\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ShopSubscriptionController extends AdminBaseController
{
    public function __construct(
        protected ShopSubscriptionRepository $repository,
        protected SubscriptionService $service,
        protected ShopSubscriptionService $shopSubscriptionService
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
        return ShopSubscriptionResource::collection($this->repository->paginate($request->all()));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param SubscriptionRequest $request
     * @return JsonResponse
     */
    public function store(SubscriptionRequest $request): JsonResponse
    {
        $validated    = $request->validated();
        $subscription = Subscription::find($validated['subscription_id']);

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language),
            $this->service->subscriptionAttach($subscription, (int) $validated['shop_id'], (int) $validated['active'])
        );
    }

    /**
     * Display the specified resource.
     *
     * @param ShopSubscription $shopSubscription
     * @return JsonResponse
     */
    public function show(ShopSubscription $shopSubscription): JsonResponse
    {
        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            ShopSubscriptionResource::make($this->repository->show($shopSubscription))
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param ShopSubscription $shopSubscription
     * @param SubscriptionRequest $request
     * @return JsonResponse
     */
    public function update(ShopSubscription $shopSubscription, SubscriptionRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $result    = $this->shopSubscriptionService->update($shopSubscription, $validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED, locale: $this->language),
            $this->shopSubscriptionService->update($shopSubscription, $validated)
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
        $this->shopSubscriptionService->delete($request->input('ids', []));

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
        $this->shopSubscriptionService->dropAll();

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_DELETED, locale: $this->language),
            []
        );
    }
}
