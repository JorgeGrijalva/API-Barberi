<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\User;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Resources\UserActivityResource;
use App\Repositories\UserActivityRepository\UserActivityRepository;
use App\Services\UserServices\UserActivityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserActivityController extends UserBaseController
{

    public function __construct(private UserActivityRepository $repository, private UserActivityService $service)
    {
        parent::__construct();
    }

    /**
     * Display a listing of resource
     *
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection
     */
    public function index(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $filter = $request->merge(['user_id' => auth('sanctum')->id()])->all();

        $userActivities = $this->repository->paginate($filter);

        return UserActivityResource::collection($userActivities);
    }

    /** Store a newly created resource in storage.
     *
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function storeMany(FilterParamsRequest $request): JsonResponse
    {
        $result = $this->service->createMany($request->input('ids', []));

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language),
        );
    }
}
