<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\Seller;

use App\Helpers\ResponseError;
use App\Http\Requests\Banner\SellerRequest;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Resources\BannerResource;
use App\Models\Banner;
use App\Models\Language;
use App\Repositories\BannerRepository\BannerRepository;
use App\Services\BannerService\BannerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BannerController extends SellerBaseController
{

    public function __construct(private BannerRepository $repository, private BannerService $service)
    {
        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     *
     * @param FilterParamsRequest $request
     * @return JsonResponse|AnonymousResourceCollection
     */
    public function paginate(FilterParamsRequest $request): JsonResponse|AnonymousResourceCollection
    {
        $filter = $request->merge(['shop_id' => $this->shop->id])->all();

        $banners = $this->repository->bannersPaginate($filter);

        return BannerResource::collection($banners);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param SellerRequest $request
     * @return JsonResponse
     */
    public function store(SellerRequest $request): JsonResponse
    {
        $validated = $request->validated();

        if (empty(data_get($validated, 'type'))) {
            $validated['type'] = 'banner';
        }

        if ($validated['type'] === Banner::LOOK) {
            $validated['shop_id'] = $this->shop->id;
        }

        $result = $this->service->create($validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language),
            BannerResource::make(data_get($result, 'data'))
        );
    }

    /**
     * Display the specified resource.
     *
     * @param Banner $banner
     * @return JsonResponse
     */
    public function show(Banner $banner): JsonResponse
    {
        $locale = Language::where('default', 1)->first()?->locale;

        $banner = $banner->loadMissing([
            'galleries',
            'translations',
            'translation' => fn($query) => $query->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
            'products.translation' => fn($query) => $query->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
        ]);

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            BannerResource::make($banner)
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Banner $banner
     * @param SellerRequest $request
     * @return JsonResponse
     */
    public function update(Banner $banner, SellerRequest $request): JsonResponse
    {
        $validated = $request->validated();

        if (empty(data_get($validated, 'type'))) {
            $validated['type'] = 'banner';
        }

        if ($validated['type'] === Banner::LOOK) {
            $validated['shop_id'] = $this->shop->id;
        }

        $result = $this->service->update($banner, $validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED, locale: $this->language),
            BannerResource::make(data_get($result, 'data'))
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
        $this->service->destroy($request->input('ids', []), $this->shop->id);

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_DELETED, locale: $this->language),
            []
        );
    }

    /**
     * Change Active Status of Model.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function setActiveBanner(int $id): JsonResponse
    {
        $banner = Banner::with([
            'products'
        ])->find($id);

        if (!empty($banner)) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        /** @var Banner $banner */
        if ($banner->type === Banner::BANNER) {
            $existShop = $banner->products->where('shop_id', $this->shop->id)->first();

            if (!empty($existShop)) {
                return $this->onErrorResponse(['code' => ResponseError::ERROR_101]);
            }
        }

        if ($banner->type === Banner::LOOK && $banner->shop_id !== $this->shop->id) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_101]);
        }

        $banner->update(['active' => !$banner->active]);

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED, locale: $this->language),
            BannerResource::make($banner)
        );
    }
}
