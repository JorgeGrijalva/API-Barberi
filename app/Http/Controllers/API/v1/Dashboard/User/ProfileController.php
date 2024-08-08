<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\User;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\Notification\UserNotificationsRequest;
use App\Http\Requests\PasswordUpdateRequest;
use App\Http\Requests\ProfileUpdateRequest;
use App\Http\Requests\SearchRequest;
use App\Http\Requests\UserCreateRequest;
use App\Http\Requests\UserCurrencyUpdateRequest;
use App\Http\Requests\UserLangUpdateRequest;
use App\Http\Resources\NotificationResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Repositories\UserRepository\UserRepository;
use App\Services\UserServices\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProfileController extends UserBaseController
{

    public function __construct(private  UserRepository $repository, private  UserService $service)
    {
        parent::__construct();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param UserCreateRequest $request
     * @return JsonResponse
     */
    public function store(UserCreateRequest $request): JsonResponse
    {
        $result = $this->service->create($request->validated());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            data_get($request, 'data')
        );
    }

    /**
     * Display the specified resource.
     *
     * @return JsonResponse
     */
    public function show(): JsonResponse
    {
        $user = $this->repository->userById(auth('sanctum')->id());

        if (empty($user)) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            UserResource::make($user)
        );
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function chatShowById(int $id): JsonResponse
    {
        $user = $this->repository->chatShowById($id);

        if (empty($user)) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            UserResource::make($user)
        );
    }

    /**
     * Display the specified resource.
     *
     * @return JsonResponse
     */
    public function adminInfo(): JsonResponse
    {
        $user = $this->repository->adminInfo();

        if (empty($user)) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            UserResource::make($user)
        );
    }

    /**
     * Display the specified resource.
     *
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function chatUsersGet(FilterParamsRequest $request): JsonResponse
    {
        $users = $this->repository->chatUsersGet((array)$request->input('ids'));

        if ($users->count() === 0) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            $users
        );
    }

    /**
     * Display the specified resource.
     *
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function notifySend(FilterParamsRequest $request): JsonResponse
    {
        $this->service->notifySend($request->all());

        return $this->successResponse(__('errors.' . ResponseError::NO_ERROR, locale: $this->language));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param ProfileUpdateRequest $request
     * @return JsonResponse
     */
    public function update(ProfileUpdateRequest $request): JsonResponse
    {

        /** @var User $user */
        $user = auth('sanctum')->user();

        $result = $this->service->update($user?->uuid, $request->validated());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            UserResource::make(data_get($result, 'data'))
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return JsonResponse
     */
    public function delete(): JsonResponse
    {
        $this->service->delete([auth('sanctum')->id()]);

        return $this->successResponse(__('errors.' . ResponseError::NO_ERROR, locale: $this->language), []);
    }

    /**
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function fireBaseTokenUpdate(FilterParamsRequest $request): JsonResponse
    {
        $result = $this->service->firebaseTokenUpdate($request->input('firebase_token'));

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED, locale: $this->language),
            []
        );
    }

    /**
     * @param PasswordUpdateRequest $request
     * @return JsonResponse
     */
    public function passwordUpdate(PasswordUpdateRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = auth('sanctum')->user();

        $result = $this->service->updatePassword($user?->uuid, $request->input('password'));

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            UserResource::make(data_get($result, 'data'))
        );
    }

    public function searchSending(SearchRequest $request): AnonymousResourceCollection
    {
        return UserResource::collection($this->repository->searchSending($request->all()));
    }

    public function notificationStatistic(): array
    {
        return $this->repository->notificationStatistic();
    }

    public function notifications(): AnonymousResourceCollection
    {
        return NotificationResource::collection($this->repository->usersNotifications());
    }

    public function notificationsUpdate(UserNotificationsRequest $request): JsonResponse
    {
        $result = $this->service->updateNotifications($request->validated());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            UserResource::make(data_get($result, 'data'))
        );
    }

    /**
     * @param UserCurrencyUpdateRequest $request
     * @return JsonResponse
     */
    public function currencyUpdate(UserCurrencyUpdateRequest $request): JsonResponse
    {
        $result = $this->service->updateCurrency((int) $request->input('currency_id'));

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            UserResource::make(data_get($result, 'data'))
        );
    }

    /**
     * @param UserLangUpdateRequest $request
     * @return JsonResponse
     */
    public function langUpdate(UserLangUpdateRequest $request): JsonResponse
    {
        $result = $this->service->updateLang($request->input('lang'));

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            UserResource::make(data_get($result, 'data'))
        );
    }
}
