<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1;

use App\Helpers\ResponseError;
use App\Http\Controllers\Controller;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\Like\StoreManyRequest;
use App\Http\Requests\Like\StoreRequest;
use App\Http\Resources\LikeResource;
use App\Models\Like;
use App\Repositories\LikeRepository\LikeRepository;
use App\Services\LikeService\LikeService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LikeController extends Controller
{
    use ApiResponse;

    public function __construct(
        private LikeRepository $repository,
        private LikeService $service
    )
    {
        parent::__construct();

        $this->middleware(['sanctum.check']);
    }

    /**
     * Display a listing of the resource.
     *
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection
     */
    public function index(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $filter = $request->merge(['user_id' => auth('sanctum')->id()])->all();

        $model  = $this->repository->paginate($filter);

        return LikeResource::collection($model);
    }

    /**
     * Display a listing of the resource.
     *
     * @param Like $like
     * @return JsonResponse
     */
    public function show(Like $like): JsonResponse
    {
        if ($like->user_id !== auth('sanctum')->id()) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        return $this->successResponse(
            __('errors.'. ResponseError::NO_ERROR, locale: $this->language),
            LikeResource::make($like->loadMissing('likable'))
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreRequest $request
     * @return JsonResponse
     */
    public function store(StoreRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['user_id'] = auth('sanctum')->id();

        $model = $this->service->store($validated);

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            LikeResource::make($model->load(['likable']))
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreManyRequest $request
     * @return JsonResponse
     */
    public function storeMany(StoreManyRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['user_id'] = auth('sanctum')->id();

        $result = $this->service->storeMany($validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            []
        );
    }

    /**
     * Update a resource.
     *
     * @param Like $like
     * @param StoreRequest $request
     * @return JsonResponse
     */
    public function update(Like $like, StoreRequest $request): JsonResponse
    {
        if ($like->user_id !== auth('sanctum')->id()) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        $validated = $request->validated();
        $validated['user_id'] = auth('sanctum')->id();

        $model = $this->service->update($like, $validated);

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            LikeResource::make($model->load(['likable']))
        );
    }

    /**
     * Delete a resource.
     *
     * @param int $id
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function destroy(int $id, FilterParamsRequest $request): JsonResponse
    {
        $result = $this->service->delete($id, $request->input('type', 'product'));

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(__('errors.' . ResponseError::NO_ERROR, locale: $this->language));
    }

}
