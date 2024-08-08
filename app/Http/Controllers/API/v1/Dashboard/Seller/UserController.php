<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\Seller;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\UserCreateRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Repositories\UserRepository\UserRepository;
use App\Services\AuthService\UserVerifyService;
use App\Services\UserServices\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends SellerBaseController
{

    public function __construct(
        private UserRepository $repository,
        private UserService $service
    )
    {
        parent::__construct();
    }

    /**
     * @param FilterParamsRequest $request
     * @return JsonResponse|AnonymousResourceCollection
     */
    public function paginate(FilterParamsRequest $request): JsonResponse|AnonymousResourceCollection
    {
        $users = $this->repository->usersPaginate($request->merge(['role' => 'user', 'active' => true])->all());

        return UserResource::collection($users);
    }

    /**
     * @param string $uuid
     * @return JsonResponse
     */
    public function show(string $uuid): JsonResponse
    {
        $user = $this->repository->userByUUID($uuid);

        if (empty($user)) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            UserResource::make($user)
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param UserCreateRequest $request
     * @return JsonResponse
     */
    public function store(UserCreateRequest $request): JsonResponse
    {
        $validated            = $request->validated();
        $validated['shop_id'] = [$this->shop->id];

        if (!empty(data_get($validated, 'email'))) {
            $validated['email_verified_at'] = now();
        }

        if (!empty(data_get($validated, 'phone'))) {
            $validated['phone_verified_at'] = now();
        }

        if (!in_array($validated['role'], ['user', 'moderator', 'deliveryman', 'master'])) {
            $validated['role'] = 'user';
        }

        $result = $this->service->create($validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        (new UserVerifyService)->verifyEmail(data_get($result, 'data'));

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            UserResource::make(data_get($result, 'data'))
        );
    }

    /**
     * @param UserUpdateRequest $request
     * @param string $uuid
     * @return JsonResponse
     */
    public function update(UserUpdateRequest $request, string $uuid): JsonResponse
    {
        $validated = $request->validated();
        $validated['shop_id'] = [$this->shop->id];

        if (!empty(data_get($validated, 'email'))) {
            $validated['email_verified_at'] = now();
        }

        if (!empty(data_get($validated, 'phone'))) {
            $validated['phone_verified_at'] = now();
        }

        $result = $this->service->update($uuid, $validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED, locale: $this->language),
            UserResource::make(data_get($result, 'data'))
        );

    }

    /**
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection
     */
    public function shopUsersPaginate(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $users = $this->repository->shopUsersPaginate($request->merge(['shop_id' => $this->shop->id])->all());

        return UserResource::collection($users);
    }

    /**
     * @param string $uuid
     * @return JsonResponse
     */
    public function shopUserShow(string $uuid): JsonResponse
    {
        /** @var User $user */
        $user = $this->repository->userByUUID($uuid);

        if ($user && $user->invite?->shop_id == $this->shop->id) {
            return $this->successResponse(
                __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
                UserResource::make($user)
            );
        }

        return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
    }

    /**
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection
     */
    public function getDeliveryman(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $filter = $request
            ->merge([
                'shop_id' => $this->shop->id,
                'role'    => 'deliveryman'
            ])
            ->all();

        $users = $this->repository->shopUsersPaginate($filter);

        return UserResource::collection($users);
    }

    /**
     * @param $uuid
     * @return JsonResponse
     */
    public function setUserActive($uuid): JsonResponse
    {
        /** @var User $user */
        $user = $this->repository->userByUUID($uuid);

        if ($user && $user->invite?->shop_id == $this->shop->id) {

            $this->service->setActive($user);

            return $this->successResponse(
                __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
                UserResource::make($user)
            );
        }

        return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
    }
}
