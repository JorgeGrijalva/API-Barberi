<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Rest;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Resources\MasterDisabledTimeResource;
use App\Models\MasterDisabledTime;
use App\Repositories\MasterDisabledTimeRepository\MasterDisabledTimeRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MasterDisabledTimeController extends RestBaseController
{
    public function __construct(private MasterDisabledTimeRepository $repository)
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

        return MasterDisabledTimeResource::collection($models);
    }

    /**
     * Display the specified resource.
     *
     * @param MasterDisabledTime $masterDisabledTime
     * @return JsonResponse
     */
    public function show(MasterDisabledTime $masterDisabledTime): JsonResponse
    {
        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            MasterDisabledTimeResource::make($this->repository->show($masterDisabledTime))
        );
    }

}
