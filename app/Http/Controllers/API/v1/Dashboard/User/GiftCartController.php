<?php

namespace App\Http\Controllers\API\v1\Dashboard\User;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\GiftCart\SendRequest;
use App\Http\Resources\GiftCartResource;
use App\Http\Resources\UserGiftCartResource;
use App\Models\GiftCart;
use App\Repositories\GiftCartRepository\UserGiftCartRepository;
use App\Services\GiftCartService\GiftCartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class GiftCartController extends UserBaseController
{
    public function __construct(private UserGiftCartRepository $repository, private GiftCartService $service)
    {
        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     *
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection
     */
    public function myCart(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $validated = $request->validated();

        $validated['active']  = 1;
        $validated['user_id'] = auth('sanctum')->id();

        $giftCarts = $this->repository->myGiftCarts($validated);

        return UserGiftCartResource::collection($giftCarts);
    }

    /**
     * @param  int  $id
     * @return JsonResponse
     */
    public function attach(int $id): JsonResponse
    {
        $giftCart = GiftCart::find($id);

        if (empty($giftCart)) {
            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_404,
                'message' => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
            ]);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            $this->service->attach($giftCart, auth('sanctum')->id())
        );
    }

    /**
     * @param SendRequest $request
     * @return JsonResponse
     */
    public function send(SendRequest $request): JsonResponse
    {
        $result = $this->service->send($request->validated());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language)
        );
    }
}
