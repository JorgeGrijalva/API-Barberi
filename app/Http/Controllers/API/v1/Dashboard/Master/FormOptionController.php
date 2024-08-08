<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\Master;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\FormOption\StoreRequest;
use App\Http\Resources\FormOptionResource;
use App\Models\FormOption;
use App\Models\Invitation;
use App\Models\User;
use App\Repositories\FormOptionRepository\FormOptionRepository;
use App\Services\FormOptionService\FormOptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FormOptionController extends MasterBaseController
{
    private int $shopId;

    public function __construct(
        private FormOptionRepository $repository,
        private FormOptionService $service
    )
    {
        parent::__construct();
        /** @var User $user */
        $user = auth('sanctum')->user();

        $this->shopId = $user
                ?->invitations
                ?->where('status', Invitation::ACCEPTED)
                ?->pluck('shop_id')
                ?->first();
    }

    /**
     * Display a listing of the resource.
     *
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection
     */
    public function index(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $models = $this->repository->paginate($request->merge(['master_id' => auth('sanctum')->id()])->all());

        return FormOptionResource::collection($models);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreRequest $request
     * @return JsonResponse
     */
    public function store(StoreRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $validated['shop_id'] = $this->shopId;
        $result = $this->service->create($validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language),
            FormOptionResource::make(data_get($result, 'data'))
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param FormOption $formOption
     * @param StoreRequest $request
     * @return JsonResponse
     */
    public function update(FormOption $formOption, StoreRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->service->update($formOption,$validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language),
            FormOptionResource::make(data_get($result, 'data'))
        );
    }

    /**
     * Display the specified resource.
     *
     * @param FormOption $formOption
     * @return JsonResponse
     */
    public function show(FormOption $formOption): JsonResponse
    {
        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            FormOptionResource::make($this->repository->show($formOption))
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
        /** @var User $user */
        $user = auth('sanctum')->user();
        $serviceMasterIds = $user->serviceMasters->pluck('id')->toArray();

        $this->service->delete($request->input('ids', []), serviceMasterIds: $serviceMasterIds);

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_DELETED, locale: $this->language),
            []
        );
    }


}
