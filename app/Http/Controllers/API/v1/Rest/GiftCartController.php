<?php

namespace App\Http\Controllers\API\v1\Rest;

use App\Http\Controllers\Controller;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Resources\GiftCartResource;
use App\Repositories\GiftCartRepository\GiftCartRepository;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class GiftCartController extends Controller
{
    public function __construct(private GiftCartRepository $repository)
    {
        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     *
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection
     */
    public function paginate(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $validated = $request->validated();

        $giftCarts = $this->repository->paginate($validated);

        return GiftCartResource::collection($giftCarts);
    }
}
