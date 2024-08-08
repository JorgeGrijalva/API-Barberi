<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\User;

use App\Helpers\ResponseError;
use App\Http\Requests\Order\AddReviewRequest;
use App\Http\Resources\ShopResource;
use App\Models\Shop;
use App\Services\ShopServices\ShopReviewService;
use Illuminate\Http\JsonResponse;

class ShopController extends UserBaseController
{

    public function __construct(private ShopReviewService $service)
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
        $shop = Shop::find($id);

        if (empty($shop)) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        $result = $this->service->addReview($shop, $request->validated());

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            ShopResource::make(data_get($result, 'data'))
        );
    }
}
