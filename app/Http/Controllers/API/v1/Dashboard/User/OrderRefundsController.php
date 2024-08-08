<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\User;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\OrderRefund\StoreRequest;
use App\Http\Resources\OrderRefundResource;
use App\Models\OrderRefund;
use App\Repositories\OrderRepository\OrderRefundRepository;
use App\Services\OrderService\OrderRefundService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderRefundsController extends UserBaseController
{

    public function __construct(private OrderRefundRepository $repository, private OrderRefundService $service)
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
        $orderRefunds = $this->repository->list($request->merge(['user_id' => auth('sanctum')->id()])->all());

        return OrderRefundResource::collection($orderRefunds);
    }

    /**
     * Display a listing of the resource.
     *
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection
     */
    public function paginate(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $orderRefunds = $this->repository->paginate($request->merge(['user_id' => auth('sanctum')->id()])->all());

        return OrderRefundResource::collection($orderRefunds);
    }

    /**
     * Display the specified resource.
     *
     * @param OrderRefund $orderRefund
     * @return JsonResponse
     */
    public function show(OrderRefund $orderRefund): JsonResponse
    {
        $orderRefund = $this->repository->show($orderRefund);

        if (data_get($orderRefund->order, 'user_id') !== auth('sanctum')->id()) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            OrderRefundResource::make($orderRefund)
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
        $result = $this->service->create($request->validated());

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
        $result = $this->service->delete($request->input('ids', []));

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_DELETED, locale: $this->language),
            []
        );
    }
}
