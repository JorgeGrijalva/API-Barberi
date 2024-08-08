<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\Seller;

use App\Helpers\ResponseError;
use App\Helpers\Utility;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\UserWorkingDay\StoreRequest;
use App\Http\Requests\UserWorkingDay\AdminRequest;
use App\Http\Resources\UserResource;
use App\Http\Resources\UserWorkingDayResource;
use App\Models\Invitation;
use App\Models\User;
use App\Repositories\UserWorkingDayRepository\UserWorkingDayRepository;
use App\Services\UserWorkingDayService\UserWorkingDayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserWorkingDayController extends SellerBaseController
{
    public function __construct(private UserWorkingDayRepository $repository, private UserWorkingDayService $service)
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
        $filter = $request->merge(['shop_id' => $this->shop->id, 'invite_status' => Invitation::ACCEPTED])->all();

        $model = $this->repository->paginate($filter);

        return UserResource::collection($model);
    }

    /**
     * Display the specified resource.
     *
     * @param AdminRequest $request
     * @return JsonResponse
     */
    public function store(AdminRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user      = Utility::checkUserInvitationByRole((int)$request->input('user_id'), 'master', $this->shop->id);

        if (!$user) {
            return $this->onErrorResponse([
                'status'  => false,
                'message' => __('errors.' . ResponseError::SELECTED_MASTER_INVALID, locale: $this->language),
                'code'    => ResponseError::ERROR_501
            ]);
        }

        $result = $this->service->create($validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(__('errors.' . ResponseError::NO_ERROR, locale: $this->language), []);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $user = Utility::checkUserInvitationByRole($id, 'master', $this->shop->id);

        if (!$user) {
            return $this->onErrorResponse([
                'status'  => false,
                'message' => __('errors.' . ResponseError::SELECTED_MASTER_INVALID, locale: $this->language),
                'code'    => ResponseError::ERROR_501
            ]);
        }

        $userWorkingDays = $this->repository->show($id);

        return $this->successResponse(__('errors.' . ResponseError::NO_ERROR, locale: $this->language), [
            'dates' => UserWorkingDayResource::collection($userWorkingDays),
            'user'  => UserResource::make(User::find($id)),
        ]);
    }

    /**
     * Update resource in storage.
     *
     * @param int $id
     * @param StoreRequest $request
     * @return JsonResponse
     */
    public function update(int $id, StoreRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user      = Utility::checkUserInvitationByRole($id, 'master', $this->shop->id);

        if (!$user) {
            return $this->onErrorResponse([
                'status'  => false,
                'message' => __('errors.' . ResponseError::SELECTED_MASTER_INVALID, locale: $this->language),
                'code'    => ResponseError::ERROR_501
            ]);
        }

        $result = $this->service->update($id, $validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED, locale: $this->language),
            []
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function destroy(FilterParamsRequest $request): JsonResponse
    {
        $this->service->delete($request->input('ids', []), $this->shop->id);

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_DELETED, locale: $this->language),
            []
        );
    }
}
