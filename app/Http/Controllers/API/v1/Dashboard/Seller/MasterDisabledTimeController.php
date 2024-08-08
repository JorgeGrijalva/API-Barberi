<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\Seller;

use App\Helpers\ResponseError;
use App\Helpers\Utility;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\MasterDisabledTime\AdminStoreRequest;
use App\Http\Resources\MasterDisabledTimeResource;
use App\Models\Invitation;
use App\Models\MasterDisabledTime;
use App\Repositories\MasterDisabledTimeRepository\MasterDisabledTimeRepository;
use App\Services\MasterDisabledTimeService\MasterDisabledTimeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MasterDisabledTimeController extends SellerBaseController
{
    public function __construct(
        private MasterDisabledTimeRepository $repository,
        private MasterDisabledTimeService $service,
        private array $userIds = [],
    )
    {
        parent::__construct();

        $this->userIds = $this
            ?->shop
            ?->invitations
            ?->where('status', Invitation::ACCEPTED)
            ?->pluck('user_id')
            ?->toArray() ?? [];
    }

    /**
     * Display a listing of the resource.
     *
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection
     */
    public function index(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $models = $this->repository->paginate(
            $request->merge(['master_ids' => $this->userIds, 'invite_status' => Invitation::ACCEPTED])->all()
        );

        return MasterDisabledTimeResource::collection($models);
    }

    /**
     * Display the specified resource.
     *
     * @param AdminStoreRequest $request
     * @return JsonResponse
     */
    public function store(AdminStoreRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = Utility::checkUserInvitationByRole((int)$request->input('master_id'), 'master', $this->shop->id);

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
     * @param MasterDisabledTime $masterDisabledTime
     * @return JsonResponse
     */
    public function show(MasterDisabledTime $masterDisabledTime): JsonResponse
    {
        if (!in_array($masterDisabledTime->master_id, $this->userIds)) {
            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_404,
                'message' => __('errors.' . ResponseError::ERROR_404, locale: $this->language),
            ]);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            MasterDisabledTimeResource::make($this->repository->show($masterDisabledTime))
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param MasterDisabledTime $masterDisabledTime
     * @param AdminStoreRequest $request
     * @return JsonResponse
     */
    public function update(MasterDisabledTime $masterDisabledTime, AdminStoreRequest $request): JsonResponse
    {
        $user = Utility::checkUserInvitationByRole($masterDisabledTime->master_id, 'master', $this->shop->id);

        if (!$user) {
            return $this->onErrorResponse([
                'status'  => false,
                'message' => __('errors.' . ResponseError::SELECTED_MASTER_INVALID, locale: $this->language),
                'code'    => ResponseError::ERROR_501
            ]);
        }

        $result = $this->service->update($masterDisabledTime, $request->validated());

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
        $this->service->delete($request->input('ids', []), ['master_ids' => $this->userIds]);

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_DELETED, locale: $this->language),
            []
        );
    }

}
