<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\Seller;

use App\Helpers\ResponseError;
use App\Http\Requests\UserAddress\AdminStoreRequest;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Resources\UserAddressResource;
use App\Models\UserAddress;
use App\Repositories\UserAddressRepository\UserAddressRepository;
use App\Services\UserAddressService\UserAddressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserAddressController extends SellerBaseController
{

    public function __construct(
        private UserAddressService $service,
        private UserAddressRepository $repository
    )
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
        $model = $this->repository->paginate($request->all());

        return UserAddressResource::collection($model);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param AdminStoreRequest $request
     * @return JsonResponse
     */
    public function store(AdminStoreRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->service->create($validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        $userAddress = data_get($result, 'data');

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language),
            UserAddressResource::make($userAddress)
        );
    }

    /**
     * @param UserAddress $userAddress
     * @return JsonResponse
     */
    public function show(UserAddress $userAddress): JsonResponse
    {
        $result = $this->repository->show($userAddress);

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            UserAddressResource::make($result)
        );
    }

}
