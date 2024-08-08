<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\Master;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\MasterDisabledTime\StoreRequest;
use App\Http\Resources\MasterDisabledTimeResource;
use App\Models\MasterDisabledTime;
use App\Repositories\MasterDisabledTimeRepository\MasterDisabledTimeRepository;
use App\Services\MasterDisabledTimeService\MasterDisabledTimeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MasterDisabledTimeController extends MasterBaseController
{
    public function __construct(
        private MasterDisabledTimeRepository $repository,
        private MasterDisabledTimeService $service
    )
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
        $models = $this->repository->paginate($request->merge(['master_id' => auth('sanctum')->id()])->all());

        return MasterDisabledTimeResource::collection($models);
    }

    /**
     * Display the specified resource.
     *
     * @param StoreRequest $request
     * @return JsonResponse
     */
    public function store(StoreRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['master_id'] = auth('sanctum')->id();

        $result = $this->service->create($validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(__('errors.' . ResponseError::NO_ERROR, locale: $this->language), []);
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

    /**
     * Store a newly created resource in storage.
     *
     * @param MasterDisabledTime $masterDisabledTime
     * @param StoreRequest $request
     * @return JsonResponse
     */
    public function update(MasterDisabledTime $masterDisabledTime, StoreRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['master_id'] = auth('sanctum')->id();

        $result = $this->service->update($masterDisabledTime, $validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED, locale: $this->language),
            []
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function destroy(FilterParamsRequest $request): JsonResponse
    {
        $this->service->delete($request->input('ids', []), ['master_id' => auth('sanctum')->id()]);

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_DELETED, locale: $this->language),
            []
        );
    }
}
