<?php
declare(strict_types=1);

namespace App\Services\ShopServices;

use App\Helpers\ResponseError;
use App\Models\Shop;
use App\Services\CoreService;

class ShopReviewService extends CoreService
{
    protected function getModelClass(): string
    {
        return Shop::class;
    }

    public function addReview(Shop $shop, $collection): array
    {
        $shop->addAssignReview($collection, $shop);

        return [
            'status' => true,
            'code'   => ResponseError::NO_ERROR,
            'data'   => $shop
        ];
    }

}
