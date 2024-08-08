<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Rest;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Resources\WarehouseResource;
use App\Repositories\WarehouseRepository\WarehouseRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WarehouseController extends RestBaseController
{
    public function __construct(private WarehouseRepository $repository)
    {
        parent::__construct();
    }

    public function index(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $models = $this->repository->paginate($request->all());

        return WarehouseResource::collection($models);
    }

    public function show(int $id): JsonResponse
    {
        $model = $this->repository->showById($id);

        if (empty($model)) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            WarehouseResource::make($model)
        );
    }
}
