<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Rest;

use App\Helpers\ResponseError;
use App\Http\Requests\CouponCheckRequest;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Resources\CouponResource;
use App\Repositories\CouponRepository\CouponRepository;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CouponController extends RestBaseController
{
    use ApiResponse;

    public function __construct(private CouponRepository $repository)
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
        $coupons = $this->repository->couponsList($request->all());

        return CouponResource::collection($coupons);
    }

    /**
     * Handle the incoming request.
     *
     * @param CouponCheckRequest $request
     * @return JsonResponse
     */
    public function check(CouponCheckRequest $request): JsonResponse
    {
        $result = $this->repository->checkCoupon($request->all());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            CouponResource::make(data_get($result, 'data'))
        );
    }
}
