<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Rest;

use App\Http\Resources\ShopSocialResource;
use App\Repositories\ShopSocialRepository\ShopSocialRepository;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ShopSocialController extends RestBaseController
{

    public function __construct(
        private ShopSocialRepository $repository,
    )
    {
        parent::__construct();
    }

    /**
     * @param int $id
     *
     * @return AnonymousResourceCollection
     */
    public function socialsByShop(int $id): AnonymousResourceCollection
    {
        $result = $this->repository->socialByShop($id);

        return ShopSocialResource::collection($result);
    }

}
