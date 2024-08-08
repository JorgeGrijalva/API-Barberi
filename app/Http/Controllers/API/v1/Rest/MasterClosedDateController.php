<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Rest;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Resources\MasterClosedDateResource;
use App\Models\MasterClosedDate;
use App\Repositories\MasterClosedDateRepository\MasterClosedDateRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MasterClosedDateController extends RestBaseController
{
    public function __construct(private MasterClosedDateRepository $repository)
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

        return MasterClosedDateResource::collection($models);
    }

    /**
     * Display the specified resource.
     *
     * @param MasterClosedDate $masterClosedDate
     * @return JsonResponse
     */
    public function show(MasterClosedDate $masterClosedDate): JsonResponse
    {
        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            MasterClosedDateResource::make($masterClosedDate->load(['master']))
        );
    }

}
