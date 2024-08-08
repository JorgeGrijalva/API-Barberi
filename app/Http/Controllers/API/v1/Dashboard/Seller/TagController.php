<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\Seller;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\Tag\StoreRequest;
use App\Http\Requests\Tag\UpdateRequest;
use App\Http\Resources\ShopTagResource;
use App\Http\Resources\TagResource;
use App\Models\Tag;
use App\Repositories\ShopTagRepository\ShopTagRepository;
use App\Repositories\TagRepository\TagRepository;
use App\Services\TagService\TagService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TagController extends SellerBaseController
{

    public function __construct(
        private TagService $service,
        private TagRepository $repository,
        private ShopTagRepository $shopTagRepository,
    )
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
        $tag = $this->repository->paginate($request->merge(['shop_id' => $this->shop->id])->all());

        return TagResource::collection($tag);
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

        $result = $this->service->create($validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language),
            []
        );
    }

    /**
     * Display the specified resource.
     *
     * @param Tag $tag
     * @return JsonResponse
     */
    public function show(Tag $tag): JsonResponse
    {
        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language), 
            TagResource::make($this->repository->show($tag))
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Tag $tag
     * @param UpdateRequest $request
     * @return JsonResponse
     */
    public function update(Tag $tag, UpdateRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->service->update($tag, $validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED, locale: $this->language),
            []
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
        $this->service->delete($request->input('ids', []), $this->shop->id);

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_DELETED, locale: $this->language)
        );
    }

    public function shopTagsPaginate(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $models = $this->shopTagRepository->paginate($request->all());

        return ShopTagResource::collection($models);
    }

}
