<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\Seller;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\Invitation\SellerRequest;
use App\Http\Requests\Invitation\StatusRequest;
use App\Http\Resources\InviteResource;
use App\Repositories\InviteRepository\InviteRepository;
use App\Services\InviteService\InviteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InviteController extends SellerBaseController
{

    public function __construct(private InviteRepository $repository, private InviteService $service)
    {
        parent::__construct();
    }

    public function paginate(FilterParamsRequest $request): JsonResponse|AnonymousResourceCollection
    {
        $invites = $this->repository->paginate($request->merge(['shop_id' => $this->shop->id])->all());

        return InviteResource::collection($invites);
    }

    /**
     * @param SellerRequest $request
     * @return JsonResponse
     */
    public function create(SellerRequest $request): JsonResponse
    {
        $data              = $request->validated();
        $data['shop_id']   = $this->shop->id;
        $data['shop_name'] = $this->shop->translation?->title;

        $result = $this->service->sellerCreate($data);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            InviteResource::make(data_get($result, 'data'))
        );
    }

    /**
     * @param int $id
     * @param StatusRequest $request
     * @return InviteResource|JsonResponse
     */
    public function changeStatus(int $id, StatusRequest $request): InviteResource|JsonResponse
    {
        $data            = $request->validated();
        $data['shop_id'] = $this->shop->id;

        $result = $this->service->changeStatus($id, $data);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            InviteResource::make(data_get($result, 'data'))
        );
    }

    /**
     * @param FilterParamsRequest $request
     * @return InviteResource|JsonResponse
     */
    public function delete(FilterParamsRequest $request): InviteResource|JsonResponse
    {
        $this->service->delete($request->input('ids'), $this->shop->id);

        return $this->successResponse(__('errors.' . ResponseError::NO_ERROR, locale: $this->language));
    }

}
