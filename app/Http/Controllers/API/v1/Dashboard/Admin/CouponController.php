<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\Admin;

use App\Helpers\ResponseError;
use App\Http\Requests\Coupon\UpdateRequest;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Resources\CouponResource;
use App\Models\Coupon;
use App\Models\Language;
use App\Repositories\CouponRepository\CouponRepository;
use App\Services\CouponService\CouponService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CouponController extends AdminBaseController
{
    public function __construct(private CouponRepository $repository, private CouponService $service)
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
     * Display a listing of the resource.
     *
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection
     */
    public function paginate(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $coupons = $this->repository->couponsPaginate($request->all());

        return CouponResource::collection($coupons);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param UpdateRequest $request
     * @return JsonResponse
     */
    public function store(UpdateRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->service->create($validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language),
            CouponResource::make(data_get($result, 'data'))
        );
    }

    /**
     * Display the specified resource.
     *
     * @param Coupon $coupon
     * @return JsonResponse
     */
    public function show(Coupon $coupon): JsonResponse
    {
        $locale  = Language::where('default', 1)->first()?->locale;

        $coupon->load([
            'translation' => fn($q) => $q
                ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                }))
                ->select('id', 'coupon_id', 'locale', 'title'),
            'translations'
        ]);

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            CouponResource::make($this->repository->show($coupon))
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Coupon $coupon
     * @param UpdateRequest $request
     * @return JsonResponse
     */
    public function update(Coupon $coupon, UpdateRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->service->update($coupon, $validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED, locale: $this->language),
            CouponResource::make(data_get($result, 'data'))
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
        $this->service->delete($request->input('ids', []), $request->input('shop_id'));

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_DELETED, locale: $this->language),
            []
        );
    }
}
