<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\Master;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\UserWorkingDay\StoreRequest;
use App\Http\Resources\UserResource;
use App\Http\Resources\UserWorkingDayResource;
use App\Repositories\UserWorkingDayRepository\UserWorkingDayRepository;
use App\Services\UserWorkingDayService\UserWorkingDayService;
use Illuminate\Http\JsonResponse;

class UserWorkingDayController extends MasterBaseController
{
    public function __construct(private UserWorkingDayRepository $repository, private UserWorkingDayService $service)
    {
        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        return $this->show();
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
        $validated['user_id'] = auth('sanctum')->id();

        $result = $this->service->create($validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(__('errors.' . ResponseError::NO_ERROR, locale: $this->language), []);
    }

    /**
     * Display the specified resource.
     * @return JsonResponse
     */
    public function show(): JsonResponse
    {
        $userWorkingDays = $this->repository->show(auth('sanctum')->id());

        return $this->successResponse(__('errors.' . ResponseError::NO_ERROR, locale: $this->language), [
            'dates' => UserWorkingDayResource::collection($userWorkingDays),
            'user'  => UserResource::make(auth('sanctum')->user()),
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
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language),
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
        $this->service->delete($request->input('ids', []), userId: auth('sanctum')->id());

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_DELETED, locale: $this->language),
            []
        );
    }
}
