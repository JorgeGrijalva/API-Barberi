<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Rest;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Resources\CityResource;
use App\Models\City;
use App\Repositories\CityRepository\CityRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CityController extends RestBaseController
{

    public function __construct(
        private CityRepository $repository,
    )
    {
        parent::__construct();
    }

    public function index(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $model = $this->repository->paginate($request->all());

        return CityResource::collection($model);
    }

    /**
     * Display the specified resource.
     *
     * @param City $city
     * @return JsonResponse
     */
    public function show(City $city): JsonResponse
    {
        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            CityResource::make($this->repository->show($city))
        );
    }

}
