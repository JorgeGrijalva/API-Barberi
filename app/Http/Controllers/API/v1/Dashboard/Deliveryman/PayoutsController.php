<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\Deliveryman;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\Payout\StoreRequest;
use App\Http\Requests\Payout\UpdateRequest;
use App\Http\Resources\PayoutResource;
use App\Models\Payout;
use App\Repositories\PayoutsRepository\PayoutsRepository;
use App\Services\PayoutService\PayoutService;
use Illuminate\Http\JsonResponse;

class PayoutsController extends DeliverymanBaseController
{

    public function __construct(private PayoutsRepository $repository, private PayoutService $service)
    {
        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     *
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function index(FilterParamsRequest $request): JsonResponse
    {
        $payouts = $this->repository->paginate($request->merge(['created_by' => auth('sanctum')->id()])->all());

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            PayoutResource::collection($payouts)
        );
    }

    /**
     * Display the specified resource.
     *
     * @param StoreRequest $request
     * @return JsonResponse
     */
    public function store(StoreRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['created_by'] = auth('sanctum')->id();

        $result = $this->service->create($validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            []
        );
    }

    /**
     * Display the specified resource.
     *
     * @param Payout $payout
     * @return JsonResponse
     */
    public function show(Payout $payout): JsonResponse
    {
        if ($payout->created_by !== auth('sanctum')->id()) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_101]);
        }

        $payout = $this->repository->show($payout);

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            PayoutResource::make($payout)
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Payout $payout
     * @param UpdateRequest $request
     * @return JsonResponse
     */
    public function update(Payout $payout, UpdateRequest $request): JsonResponse
    {
        if ($payout->created_by !== auth('sanctum')->id()) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_101]);
        }

        $result = $this->service->update($payout, $request->validated());

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
        $this->service->delete($request->input('ids', []));

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_DELETED, locale: $this->language),
            []
        );
    }
}
