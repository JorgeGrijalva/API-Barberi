<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\Admin;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\MasterClosedDate\AdminStoreRequest;
use App\Http\Resources\MasterClosedDateResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Repositories\MasterClosedDateRepository\MasterClosedDateRepository;
use App\Services\MasterClosedDateService\MasterClosedDateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MasterClosedDateController extends AdminBaseController
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
        $models = $this->repository->paginate($request->all());

        return MasterClosedDateResource::collection($models);
    }

    /**
     * Display the specified resource.
     *
     * @param AdminStoreRequest $request
     * @return JsonResponse
     */
    public function store(AdminStoreRequest $request): JsonResponse
    {
        $validated = $request->validated();

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
        $master = User::find($id);

        if (empty($master)) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        $shopClosedDate = $this->repository->show($master->id);

        return $this->successResponse(__('errors.' . ResponseError::NO_ERROR, locale: $this->language), [
            'closed_dates' => MasterClosedDateResource::collection($shopClosedDate),
            'master'       => UserResource::make($master),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param int $id
     * @param AdminStoreRequest $request
     * @return JsonResponse
     */
    public function update(int $id, AdminStoreRequest $request): JsonResponse
    {
        $master = User::find($id);

        if (empty($master)) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        $result = $this->service->update($master->id, $request->validated());

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
        $this->service->delete($request->input('ids', []), $request->all());

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
