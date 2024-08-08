<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\User;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Resources\UserMemberShipResource;
use App\Models\UserMemberShip;
use App\Repositories\MemberShipRepository\UserMemberShipRepository;
use App\Services\MemberShipService\UserMemberShipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MemberShipController extends UserBaseController
{
    public function __construct(private UserMemberShipRepository $repository, private UserMemberShipService $service)
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
        $models = $this->repository->paginate($request->merge(['user_id' => auth('sanctum')->id()])->all());

        return UserMemberShipResource::collection($models);
    }

    /**
     * Display the specified resource.
     *
     * @param UserMemberShip $userMemberShip
     * @return JsonResponse
     */
    public function show(UserMemberShip $userMemberShip): JsonResponse
    {
        if ($userMemberShip->user_id !== auth('sanctum')->id()) {
            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_404,
                'message' => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
            ]);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            UserMemberShipResource::make($this->repository->show($userMemberShip))
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
        $this->service->delete($request->input('ids', []), ['user_id' => auth('sanctum')->id()]);

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_DELETED, locale: $this->language),
            []
        );
    }

}
