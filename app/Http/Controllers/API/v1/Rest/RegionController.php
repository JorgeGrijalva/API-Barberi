<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Rest;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Resources\RegionResource;
use App\Models\Region;
use App\Repositories\RegionRepository\RegionRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RegionController extends RestBaseController
{

    public function __construct(
        private RegionRepository $repository,
    )
    {
        parent::__construct();
    }

    /**
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection
     */
    public function index(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $model = $this->repository->paginate($request->all());

        return RegionResource::collection($model);
    }

    /**
     * Display the specified resource.
     *
     * @param Region $region
     * @return JsonResponse
     */
    public function show(Region $region): JsonResponse
    {
        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            RegionResource::make($this->repository->show($region))
        );
    }

}
