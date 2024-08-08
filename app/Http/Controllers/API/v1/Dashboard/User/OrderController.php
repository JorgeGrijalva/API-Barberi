<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\User;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\Order\AddReviewRequest;
use App\Http\Requests\Order\StatusUpdateRequest;
use App\Http\Requests\Order\UserStoreRequest;
use App\Http\Resources\OrderDetailResource;
use App\Http\Resources\OrderResource;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Settings;
use App\Models\User;
use App\Repositories\OrderRepository\OrderRepository;
use App\Services\OrderService\OrderReviewService;
use App\Services\OrderService\OrderService;
use App\Services\OrderService\OrderStatusUpdateService;
use App\Traits\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderController extends UserBaseController
{
    use Notification;

    public function __construct(private OrderRepository $repository, private OrderService $service)
    {
        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     *
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection
     */
    public function paginate(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $filter = $request->merge(['user_id' => auth('sanctum')->id()])->all();

        $orders = $this->repository->ordersPaginate($filter);

        return OrderResource::collection($orders);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param UserStoreRequest $request
     * @return JsonResponse
     */
    public function store(UserStoreRequest $request): JsonResponse
    {
        $validated = $request->validated();

        if ((int)Settings::where('key', 'order_auto_approved')->first()?->value === 1) {
            $validated['status'] = Order::STATUS_ACCEPTED;
        }

        $validated['user_id'] = auth('sanctum')->id();

        $cart = Cart::with([
            'userCarts:id,cart_id',
            'userCarts.cartDetails:id,user_cart_id'
        ])
        ->withCount('userCarts')
        ->select('id')
        ->find(data_get($validated, 'cart_id'));

        if (empty($cart)) {
            return $this->onErrorResponse([
                'code'      => ResponseError::ERROR_404,
                'message'   => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
            ]);
        }

        /** @var Cart $cart */
        if ($cart->user_carts_count === 0) {
            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_400,
                'message' => __('errors.' . ResponseError::USER_CARTS_IS_EMPTY, locale: $this->language)
            ]);
        }

        if ($cart->userCarts()->withCount('cartDetails')->get()->sum('cart_details_count') === 0) {
            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_400,
                'message' => __('errors.' . ResponseError::PRODUCTS_IS_EMPTY, locale: $this->language)
            ]);
        }

        $result = $this->service->create($validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        $this->adminNotify($result);
        $this->notify($result);

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language),
            OrderResource::collection(data_get($result, 'data'))
        );
    }

    /**
     * @param $result
     * @return void
     */
    public function notify($result): void
    {
        foreach (data_get($result, 'data', []) as $order) {

            if (!$order?->shop?->user_id) {
                continue;
            }

            $seller = User::select(['firebase_token', 'id', 'lang'])->find($order->shop->user_id);

            $this->sendUsers($order, [$seller]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $order = $this->repository->orderById($id, userId: auth('sanctum')->id());

        if ($order?->user_id !== auth('sanctum')->id()) {
            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_404,
                'message' => __('errors.' . ResponseError::ORDER_NOT_FOUND, locale: $this->language)
            ]);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            OrderResource::make($order),
        );
    }

    public function ordersByParentId(int $id): AnonymousResourceCollection
    {
        $orders = $this->repository->ordersByParentId($id, userId: auth('sanctum')->id());

        return OrderResource::collection($orders);
    }

    /**
     * Add Review to Order.
     *
     * @param int $id
     * @param AddReviewRequest $request
     * @return JsonResponse
     */
    public function addOrderReview(int $id, AddReviewRequest $request): JsonResponse
    {
        /** @var Order $order */
        $order = Order::with(['shop:id'])->select(['id', 'user_id'])->find($id);

        if ($order?->user_id !== auth('sanctum')->id()) {
            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_404,
                'message' =>  __('errors.' . ResponseError::ORDER_NOT_FOUND, locale: $this->language)
            ]);
        }

        $result = (new OrderReviewService)->addReview($order, $request->validated());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            ResponseError::NO_ERROR,
            OrderDetailResource::make(data_get($result, 'data'))
        );
    }

    /**
     * Add Review to Deliveryman.
     *
     * @param int $id
     * @param AddReviewRequest $request
     * @return JsonResponse
     */
    public function addDeliverymanReview(int $id, AddReviewRequest $request): JsonResponse
    {
        $result = (new OrderReviewService)->addDeliverymanReview($id, $request->validated());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            ResponseError::NO_ERROR,
            OrderDetailResource::make(data_get($result, 'data'))
        );
    }

    /**
     * @param int $id
     * @param StatusUpdateRequest $request
     * @return JsonResponse
     */
    public function orderStatusChange(int $id, StatusUpdateRequest $request): JsonResponse
    {
        /** @var Order  $order */
        $order = Order::with([
            'deliveryman:id,lang,firebase_token',
            'user.notifications',
            'orderDetails:id,order_id',
            'shop:id,user_id',
            'shop.seller:id,lang,firebase_token',
            'user.wallet',
            'transaction.paymentSystem',
            'notes',
        ])->find($id);

        if (!$order) {
            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_404,
                'message' => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
            ]);
        }

        if ($order->status !== Order::STATUS_NEW) {
            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_254,
                'message' => __('errors.' . ResponseError::ERROR_254, locale: $this->language)
            ]);
        }

        $result = (new OrderStatusUpdateService)->statusUpdate($order, $request->validated());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            ResponseError::NO_ERROR,
            OrderResource::make(data_get($result, 'data')),
        );
    }

    public function getActiveOrders(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $filter = $request->merge([
            'user_id'  => auth('sanctum')->id(),
            'statuses' => [
                Order::STATUS_NEW,
                Order::STATUS_ACCEPTED,
                Order::STATUS_READY,
                Order::STATUS_ON_A_WAY,
            ]
        ])->all();

        $orders = $this->repository->simpleOrdersPaginate($filter);

        return OrderResource::collection($orders);
    }

    public function getCompletedOrders(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $filter = $request->merge([
            'user_id'  => auth('sanctum')->id(),
            'statuses' => [
                Order::STATUS_DELIVERED,
                Order::STATUS_CANCELED,
            ]
        ])->all();

        $orders = $this->repository->simpleOrdersPaginate($filter);

        return OrderResource::collection($orders);
    }

}
