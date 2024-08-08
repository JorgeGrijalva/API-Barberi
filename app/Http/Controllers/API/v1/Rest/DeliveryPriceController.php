<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Rest;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Resources\DeliveryPriceResource;
use App\Models\DeliveryPrice;
use App\Repositories\DeliveryPriceRepository\DeliveryPriceRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DeliveryPriceController extends RestBaseController
{

    public function __construct(
        private DeliveryPriceRepository $repository
    )
    {
        parent::__construct();
    }

    public function index(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $model = $this->repository->paginate($request->all());

        return DeliveryPriceResource::collection($model);
    }

    /**
     * Display the specified resource.
     *
     * @param DeliveryPrice $deliveryPrice
     * @return JsonResponse
     */
    public function show(DeliveryPrice $deliveryPrice): JsonResponse
    {
        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            DeliveryPriceResource::make($this->repository->show($deliveryPrice))
        );

    }
}
