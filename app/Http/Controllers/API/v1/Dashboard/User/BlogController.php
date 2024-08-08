<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\User;

use App\Helpers\ResponseError;
use App\Http\Requests\Order\AddReviewRequest;
use App\Http\Resources\BlogResource;
use App\Models\Blog;
use App\Services\BlogService\BlogReviewService;
use Illuminate\Http\JsonResponse;

class BlogController extends UserBaseController
{

    public function __construct(private BlogReviewService $service)
    {
        parent::__construct();
    }

    /**
     * Add review to Shop
     *
     * @param int $id
     * @param AddReviewRequest $request
     * @return JsonResponse
     */
    public function addReviews(int $id, AddReviewRequest $request): JsonResponse
    {
        $model = Blog::find($id);

        if (empty($model)) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        $result = $this->service->addReview($model, $request->validated());

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            BlogResource::make(data_get($result, 'data'))
        );
    }
}
