<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\User;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\MasterClosedDate\StoreRequest;
use App\Http\Resources\MasterClosedDateResource;
use App\Http\Resources\UserResource;
use App\Repositories\MasterClosedDateRepository\MasterClosedDateRepository;
use App\Services\MasterClosedDateService\MasterClosedDateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MasterClosedDateController extends UserBaseController
{
    public function __construct(
        private MasterClosedDateRepository $repository,
        private MasterClosedDateService $service
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

        return MasterClosedDateResource::collection($models);
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
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $shopClosedDate = $this->repository->show(auth('sanctum')->id());

        return $this->successResponse(__('errors.' . ResponseError::NO_ERROR, locale: $this->language), [
            'closed_dates' => MasterClosedDateResource::collection($shopClosedDate),
            'master'       => UserResource::make(auth('sanctum')->user()),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param int $id
     * @param StoreRequest $request
     * @return JsonResponse
     */
    public function update(int $id, StoreRequest $request): JsonResponse
    {
        $result = $this->service->update(auth('sanctum')->id(), $request->validated());

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
