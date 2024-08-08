<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\Master;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Resources\ServiceResource;
use App\Models\Invitation;
use App\Models\Service;
use App\Models\User;
use App\Repositories\ServiceRepository\ServiceRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ServiceController extends MasterBaseController
{
    private array $shopIds;

    public function __construct(private ServiceRepository $repository)
    {
        parent::__construct();

        /** @var User $user */
        $user = auth('sanctum')->user();

        $this->shopIds = $user
            ?->invitations
            ?->where('status', Invitation::ACCEPTED)
            ?->pluck('shop_id')
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
        $filter = $request->merge(['shop_ids' => $this->shopIds])->all();

        $models = $this->repository->paginate($filter);

        return ServiceResource::collection($models);
    }

    /**
     * Display the specified resource.
     *
     * @param Service $service
     * @return JsonResponse
     */
    public function show(Service $service): JsonResponse
    {
        if (!in_array($service->shop_id, $this->shopIds)) {
            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_404,
                'message' => __('errors.' . ResponseError::ERROR_404, locale: $this->language),
            ]);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            ServiceResource::make($this->repository->show($service))
        );
    }
}
