<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\User;

use App\Helpers\ResponseError;
use App\Http\Requests\Order\AddReviewRequest;
use App\Services\ProductService\ProductReviewService;
use Illuminate\Http\JsonResponse;

class ProductController extends UserBaseController
{

    public function __construct(private ProductReviewService $service)
    {
        parent::__construct();
    }

    /**
     * Add review to product
     *
     * @param string $uuid
     * @param AddReviewRequest $request
     * @return JsonResponse
     */
    public function addProductReview(string $uuid, AddReviewRequest $request): JsonResponse
    {
        $result = $this->service->addReview($uuid, $request);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        return $this->successResponse(__('errors.' . ResponseError::NO_ERROR, locale: $this->language), []);
    }

}
