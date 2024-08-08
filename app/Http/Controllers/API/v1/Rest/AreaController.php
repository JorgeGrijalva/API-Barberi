<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Rest;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Resources\AreaResource;
use App\Models\Area;
use App\Repositories\AreaRepository\AreaRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AreaController extends RestBaseController
{

    public function __construct(
        private AreaRepository $repository,
    )
    {
        parent::__construct();
    }

    public function index(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $model = $this->repository->paginate($request->all());

        return AreaResource::collection($model);
    }

    /**
     * Display the specified resource.
     *
     * @param Area $area
     * @return JsonResponse
     */
    public function show(Area $area): JsonResponse
    {
        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            AreaResource::make($this->repository->show($area))
        );
    }

}
