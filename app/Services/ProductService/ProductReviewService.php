<?php
declare(strict_types=1);

namespace App\Services\ProductService;

use App\Helpers\ResponseError;
use App\Models\Product;
use App\Services\CoreService;

class ProductReviewService extends CoreService
{

    protected function getModelClass(): string
    {
        return Product::class;
    }

    public function addReview($uuid, $collection): array
    {
        /** @var Product $product */

        $product = $this->model()
            ->with([
                'shop'
            ])
            ->firstWhere('uuid', $uuid);

        if (empty(data_get($product, 'id'))) {
            return ['status' => false, 'code' => ResponseError::ERROR_404];
        }

        $product->addAssignReview($collection, $product->shop);

        return [
            'status' => true,
            'code' => ResponseError::NO_ERROR,
            'data' => $product
        ];
    }

    /**
     * @param $uuid
     * @return array
     */
    public function reviews($uuid): array
    {
        /** @var Product $product */

        $product = $this->model()
            ->with([
                'reviews.user:id,firstname,lastname,img,active',
                'reviews.galleries',
            ])
            ->firstWhere('uuid', $uuid);

        if (empty(data_get($product, 'id'))) {
            return ['status' => false, 'code' => ResponseError::ERROR_404];
        }

        return [
            'status' => true,
            'code' => ResponseError::NO_ERROR,
            'data' => $product->reviews
        ];
    }
}
