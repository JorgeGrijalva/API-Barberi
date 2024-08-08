<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Rest;

use App\Helpers\ResponseError;
use App\Http\Controllers\Controller;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\Review\AddedReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Repositories\ReviewRepository\ReviewRepository;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ReviewController extends Controller
{
    use ApiResponse;

    public function __construct(private ReviewRepository $repository)
    {
        parent::__construct();
    }

    /**
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection
     */
    public function reviews(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $result = $this->repository->paginate($request->all(), [
            'user' => fn($q) => $q
                ->select([
                    'id',
                    'uuid',
                    'firstname',
                    'lastname',
                    'password',
                    'img',
                    'active',
                ]),
        ]);

        return ReviewResource::collection($result);
    }

    /**
     * @param int|string $id
     * @return float[]
     */
    public function reviewsGroupByRating(int|string $id): array
    {
        return $this->repository->reviewsGroupByRating((int)$id);
    }

    /**
     * @param AddedReviewRequest $request
     * @return JsonResponse
     */
    public function addedReview(AddedReviewRequest $request): JsonResponse
    {
        $filter = $request->validated();
        $filter['user_id'] = auth('sanctum')->id();

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            $this->repository->addedReview($filter)
        );
    }

    /**
     * @param AddedReviewRequest $request
     * @return JsonResponse
     */
    public function modelOptions(AddedReviewRequest $request): JsonResponse
    {
        $filter = $request->validated();

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            $this->repository->modelOptions($filter)
        );
    }
}
