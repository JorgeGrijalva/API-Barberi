<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\Admin;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Resources\UserGiftCartResource;
use App\Models\UserGiftCart;
use App\Repositories\GiftCartRepository\UserGiftCartRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserGiftCartController extends AdminBaseController
{
    public function __construct(private UserGiftCartRepository $repository)
    {
        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     *
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection
     */
    public function index(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $models = $this->repository->paginate($request->all());

        return UserGiftCartResource::collection($models);
    }

    /**
     * Display the specified resource.
     *
     * @param  UserGiftCart $userGiftCart
     * @return JsonResponse
     */
    public function show(UserGiftCart $userGiftCart): JsonResponse
    {
        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            UserGiftCartResource::make($this->repository->show($userGiftCart))
        );
    }
}
