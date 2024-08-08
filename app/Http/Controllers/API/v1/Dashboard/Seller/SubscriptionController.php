<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\Seller;

use App\Helpers\ResponseError;
use App\Http\Resources\ShopSubscriptionResource;
use App\Http\Resources\SubscriptionResource;
use App\Models\ShopSubscription;
use App\Models\Subscription;
use App\Services\SubscriptionService\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Schema;

class SubscriptionController extends SellerBaseController
{

    public function __construct(private SubscriptionService $service)
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
        $subscriptions = Subscription::where('active', 1)->get();

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            SubscriptionResource::collection($subscriptions)
        );
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function mySubscription(Request $request): JsonResponse
    {
        $column = $request->input('column', 'id');

        if ($column !== 'id') {
            $column = Schema::hasColumn('shop_subscriptions', $column) ? $column : 'id';
        }

        $subscriptions = ShopSubscription::actualSubscription()
            ->with([
                'subscription',
                'transaction'
            ])
            ->where('shop_id', $this->shop->id)
            ->orderBy($column, $request->input('sort','desc'))
            ->paginate($request->input('perPage', 10));

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            ShopSubscriptionResource::collection($subscriptions)
        );
    }

    /**
     * @param  int  $id
     * @return JsonResponse
     */
    public function subscriptionAttach(int $id): JsonResponse
    {
        $subscription = Subscription::find($id);

        if (empty($subscription)) {
            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_404,
                'message' => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
            ]);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            $this->service->subscriptionAttach($subscription, $this->shop->id)
        );
    }
}
