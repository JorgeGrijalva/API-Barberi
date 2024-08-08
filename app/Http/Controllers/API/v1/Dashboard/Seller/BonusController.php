<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\Seller;

use App\Helpers\ResponseError;
use App\Http\Requests\Bonus\StoreRequest;
use App\Http\Requests\Bonus\UpdateRequest;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Resources\Bonus\BonusResource;
use App\Models\Bonus;
use App\Repositories\BonusRepository\BonusRepository;
use App\Services\BonusService\BonusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BonusController extends SellerBaseController
{

    public function __construct(private BonusService $service, private BonusRepository $repository)
    {
        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     *
     * @param FilterParamsRequest $request
     * @return JsonResponse|AnonymousResourceCollection
     */
    public function index(FilterParamsRequest $request): JsonResponse|AnonymousResourceCollection
    {
        $bonus = $this->repository->paginate($request->merge(['shop_id' => $this->shop->id])->all());

        return BonusResource::collection($bonus);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreRequest $request
     * @return JsonResponse
     */
    public function store(StoreRequest $request): JsonResponse
    {
        $data            = $request->validated();
        $data['shop_id'] = $this->shop->id;

        $result = $this->service->create($data);

        if (!data_get($result, 'status')) {
            return  $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language),
            BonusResource::make(data_get($result, 'data'))
        );
    }

    /**
     * Display the specified resource.
     *
     * @param Bonus $bonus
     * @return JsonResponse
     */
    public function show(Bonus $bonus): JsonResponse
    {
        $shopBonus = $this->repository->show($bonus);

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            BonusResource::make($shopBonus)
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Bonus $bonus
     * @param UpdateRequest $request
     * @return JsonResponse
     */
    public function update(Bonus $bonus, UpdateRequest $request): JsonResponse
    {
        $data             = $request->validated();
        $data['shop_id']  = $this->shop->id;

        $result = $this->service->update($bonus, $data);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED, locale: $this->language),
            BonusResource::make(data_get($result, 'data'))
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
        $result = $this->service->delete($request->input('ids', []), $this->shop->id);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_DELETED, locale: $this->language),
            []
        );
    }

    /**
     * @param int $id
     * @return JsonResponse
     */
    public function statusChange(int $id): JsonResponse
    {
        $result = $this->service->statusChange($id);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            BonusResource::make(data_get($result, 'data'))
        );
    }

}
