<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Rest;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Resources\ServiceExtraResource;
use App\Models\ServiceExtra;
use App\Repositories\ServiceExtraRepository\ServiceExtraRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ServiceExtraController extends RestBaseController
{
    public function __construct(protected ServiceExtraRepository $repository)
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
        $models = $this->repository->paginate($request->all());

        return ServiceExtraResource::collection($models);
    }

    /**
     * Display the specified resource.
     *
     * @param  ServiceExtra $serviceExtra
     * @return JsonResponse
     */
    public function show(ServiceExtra $serviceExtra): JsonResponse
    {
        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            ServiceExtraResource::make($this->repository->show($serviceExtra))
        );
    }
}
