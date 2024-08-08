<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\Admin;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\UserWorkingDay\AdminRequest;
use App\Http\Requests\UserWorkingDay\StoreRequest;
use App\Http\Resources\UserResource;
use App\Http\Resources\UserWorkingDayResource;
use App\Models\User;
use App\Repositories\UserWorkingDayRepository\UserWorkingDayRepository;
use App\Services\UserWorkingDayService\UserWorkingDayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserWorkingDayController extends AdminBaseController
{
    public function __construct(private UserWorkingDayRepository $repository, private UserWorkingDayService $service)
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

        return UserResource::collection($models);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $user = User::find($id);

        if (empty($user)) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        $model = $this->repository->show($user->id);

        return $this->successResponse(__('errors.' . ResponseError::NO_ERROR, locale: $this->language), [
            'dates' => UserWorkingDayResource::collection($model),
            'user'  => UserResource::make($user),
        ]);
    }

    /**
     * Store a newly created resource in model.
     *
     * @param AdminRequest $request
     * @return JsonResponse
     */
    public function store(AdminRequest $request): JsonResponse
    {
        $result = $this->service->create($request->validated());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(__('errors.' . ResponseError::NO_ERROR, locale: $this->language), []);
    }

    /**
     * Update the specified resource in model.
     *
     * @param int $id
     * @param StoreRequest $request
     * @return JsonResponse
     */
    public function update(int $id, StoreRequest $request): JsonResponse
    {
        $user = User::find($id);

        if (empty($user)) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        $result = $this->service->update($user->id, $request->validated());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED, locale: $this->language),
            []
        );
    }

    /**
     * destroy resource in model.
     *
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function destroy(FilterParamsRequest $request): JsonResponse
    {
        $this->service->delete($request->input('ids', []));

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_DELETED, locale: $this->language),
            []
        );
    }

    /**
     * @return JsonResponse
     */
    public function dropAll(): JsonResponse
    {
        $this->service->dropAll();

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_DELETED, locale: $this->language),
            []
        );
    }
}
