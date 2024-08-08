<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Rest;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Resources\CountryResource;
use App\Models\Country;
use App\Repositories\CountryRepository\CountryRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CountryController extends RestBaseController
{

    public function __construct(
        private CountryRepository $repository,
    )
    {
        parent::__construct();
    }

    public function index(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $model = $this->repository->paginate($request->all());

        return CountryResource::collection($model);
    }

    /**
     * Display the specified resource.
     *
     * @param Country $country
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function show(Country $country, FilterParamsRequest $request): JsonResponse
    {
        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            CountryResource::make($this->repository->show($country, $request->all()))
        );
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function checkCountry(int $id, FilterParamsRequest $request): JsonResponse
    {
        $result = $this->repository->checkCountry($id, $request->all());

        if (empty($result)) {
            return $this->onErrorResponse([
                'code' => ResponseError::ERROR_400,
                'message' => __('errors.' . ResponseError::ERROR_400, locale: $this->language)
            ]);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            CountryResource::make($result)
        );
    }

}
