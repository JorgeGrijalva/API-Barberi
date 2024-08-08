<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Rest;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Resources\BannerResource;
use App\Repositories\BannerRepository\BannerRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BannerController extends RestBaseController
{

    public function __construct(private BannerRepository $repository)
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
        $banners = $this->repository->bannersPaginate($request->merge(['active' => 1])->all());

        return BannerResource::collection($banners);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @param FilterParamsRequest $request
     *
     * @return JsonResponse
     */
    public function show(int $id, FilterParamsRequest $request): JsonResponse
    {
        $filter = $request->merge(['status' => 'published'])->all();

        $banner = $this->repository->bannerDetails($id, $filter);

        if (empty($banner)) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            BannerResource::make($banner)
        );
    }

}
