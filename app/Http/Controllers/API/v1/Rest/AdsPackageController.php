<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Rest;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Resources\AdsPackageResource;
use App\Models\AdsPackage;
use App\Repositories\AdsPackageRepository\AdsPackageRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdsPackageController extends RestBaseController
{
    public function __construct(private AdsPackageRepository $repository)
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
        $models = $this->repository->index($request->merge(['active' => 1])->all());

        return AdsPackageResource::collection($models);
    }

    /**
     * Display a listing of the resource.
     *
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection
     */
    public function adsProducts(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $models = $this->repository->adsProducts($request->merge(['active' => 1])->all());

        return AdsPackageResource::collection($models);
    }

    /**
     * Display the specified resource.
     *
     * @param AdsPackage $adsPackage
     * @return JsonResponse
     */
    public function show(AdsPackage $adsPackage): JsonResponse
    {
        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            AdsPackageResource::make($this->repository->show($adsPackage))
        );
    }

}
