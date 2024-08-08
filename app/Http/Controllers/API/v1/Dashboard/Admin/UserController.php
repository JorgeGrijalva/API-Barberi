<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\Admin;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\PasswordUpdateRequest;
use App\Http\Requests\UserCreateRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Http\Resources\UserResource;
use App\Http\Resources\WalletHistoryResource;
use App\Models\User;
use App\Repositories\UserRepository\UserRepository;
use App\Repositories\WalletRepository\WalletHistoryRepository;
use App\Services\AuthService\UserVerifyService;
use App\Services\UserServices\UserService;
use App\Services\UserServices\UserWalletService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends AdminBaseController
{

    public function __construct(private UserService $service, private UserRepository $repository)
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
        $users = $this->repository->usersPaginate($request->all());

        return UserResource::collection($users);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param UserCreateRequest $request
     * @return JsonResponse
     */
    public function store(UserCreateRequest $request): JsonResponse
    {
        $validated = $request->validated();

        if (!empty(data_get($validated, 'email'))) {
            $validated['email_verified_at'] = now();
        }

        if (!empty(data_get($validated, 'phone'))) {
            $validated['phone_verified_at'] = now();
        }

        $result = $this->service->create($validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        (new UserVerifyService)->verifyEmail(data_get($result, 'data'));

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            UserResource::make(data_get($result, 'data'))
        );
    }

    /**
     * Display the specified resource.
     *
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
     * Update the specified resource in storage.
     *
     * @param UserUpdateRequest $request
     * @param string $uuid
     * @return JsonResponse
     */
    public function update(UserUpdateRequest $request, string $uuid): JsonResponse
    {
        $validated = $request->validated();

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
     * Remove the specified resource from storage.
     *
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function destroy(FilterParamsRequest $request): JsonResponse
    {
        $result = $this->service->delete($request->input('ids', []));

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_DELETED, locale: $this->language),
            []
        );
    }

    public function updateRole($uuid, FilterParamsRequest $request): JsonResponse
    {
        try {
            $user = $this->repository->userByUUID($uuid);

            if (empty($user)) {
                return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
            }

            /** @var User $user */
            if (
                $user->shop && $user->shop->status == 'approved' ||
                $user->role == 'seller' || $request->input('role') == 'seller'
            ) {
                return $this->onErrorResponse(['code' => ResponseError::ERROR_110]);
            }

            $user->syncRoles([$request->input('role')]);

            return $this->successResponse(
                __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED, locale: $this->language),
                UserResource::make($user)
            );

        } catch (Exception $e) {
            $this->error($e);
            return $this->onErrorResponse(['code' => ResponseError::ERROR_400]);
        }
    }

    /**
     * @param string $uuid
     * @param PasswordUpdateRequest $request
     * @return JsonResponse
     */
    public function passwordUpdate(string $uuid, PasswordUpdateRequest $request): JsonResponse
    {
        $result = $this->service->updatePassword($uuid, $request->input('password'));

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED, locale: $this->language),
            UserResource::make(data_get($result, 'data'))
        );
    }

    /**
     * @param string $uuid
     * @return JsonResponse
     */
    public function loginAsUser(string $uuid): JsonResponse
    {
        $result = $this->service->loginAsUser($uuid);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            data_get($result, 'data')
        );
    }

    public function usersSearch(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $users = $this->repository->usersSearch(
            $request->merge(['active' => true])->all()
        );

        return UserResource::collection($users);
    }

    /**
     * @return JsonResponse
     */
    public function dropAll(): JsonResponse
    {
        $this->service->dropAll();

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_DELETED, locale: $this->language),
            []
        );
    }

    public function setActive(string $uuid): JsonResponse
    {
        $user = $this->repository->userByUUID($uuid);

        if (empty($user)) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        /** @var User $user */
        $user->active = !$user->active;
        $user->save();

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED, locale: $this->language),
            UserResource::make($user)
        );
    }

    /**
     * Top up User Wallet by UUID
     *
     * @param string $uuid
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function topUpWallet(string $uuid, FilterParamsRequest $request): JsonResponse
    {
        $user = User::with('wallet.histories')->firstWhere('uuid', $uuid);

        if (empty($user)) {
            return $this->onErrorResponse([
                'code' => ResponseError::ERROR_404
            ]);
        }

        /** @var User $user */
        $result = (new UserWalletService)->update($user, [
                'price' => $request->input('price'),
                'note'  => $request->input('note')
            ]
        );

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED, locale: $this->language),
            UserResource::make(data_get($result, 'data'))
        );
    }

    /**
     * Get User Wallet History by UUID
     *
     * @param string $uuid
     * @return AnonymousResourceCollection|JsonResponse
     */
    public function walletHistories(string $uuid): JsonResponse|AnonymousResourceCollection
    {
        $user = User::with('wallet')->firstWhere('uuid', $uuid);

        if (empty($user)) {
            return $this->onErrorResponse([
                'code'      => ResponseError::ERROR_404,
                'message'   => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
            ]);
        }

        /** @var User $user */
        if (empty($user->wallet?->uuid)) {
            $user = (new UserWalletService)->create($user);
        }

        $histories = (new WalletHistoryRepository)->walletHistoryPaginate(
            ['wallet_uuid' => $user->wallet?->uuid],
        );

        return WalletHistoryResource::collection($histories);
    }
}
