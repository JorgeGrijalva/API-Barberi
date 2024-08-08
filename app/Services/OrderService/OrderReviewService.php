<?php
declare(strict_types=1);

namespace App\Services\OrderService;

use App\Helpers\ResponseError;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Services\CoreService;

class OrderReviewService extends CoreService
{

    protected function getModelClass(): string
    {
        return OrderDetail::class;
    }

    public function addReview(Order $order, $collection): array
    {
        $order->addAssignReview($collection, $order->shop);

        return [
            'status' => true,
            'code'   => ResponseError::NO_ERROR,
            'data'   => $order->load(['reviews.assignable'])
        ];
    }

    public function addDeliverymanReview($id, $collection): array
    {
        /** @var Order $order */
        $order = Order::with(['deliveryman', 'reviews'])->find($id);

        if (!$order?->deliveryman) {
            return [
                'status'    => false,
                'code'      => ResponseError::ERROR_400,
                'message'   => __('errors.' . ResponseError::ORDER_OR_DELIVERYMAN_IS_EMPTY, locale: $this->language)
            ];
        }

        $order->addAssignReview($collection, $order->deliveryman);

        return [
            'status' => true,
            'code'   => ResponseError::NO_ERROR,
            'data'   => $order->load(['reviews.assignable'])
        ];
    }

    public function addReviewByDeliveryman($id, $collection): array
    {
        /** @var Order $order */
        $order = $this->model()
            ->with([
                'order.user',
                'review',
                'reviews',
            ])->find($id);

        if (!$order || $order->deliveryman?->id !== auth('sanctum')->id() || !$order->user) {
            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_404,
                'message' =>  __('errors.' . ResponseError::ORDER_NOT_FOUND, locale: $this->language)
            ];
        }

        $order->addAssignReview($collection, $order->user);

        return [
            'status' => true,
            'code'   => ResponseError::NO_ERROR,
            'data'   => $order->load(['reviews.assignable'])
        ];
    }

}
