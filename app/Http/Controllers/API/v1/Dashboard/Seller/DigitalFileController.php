<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\Seller;

use App\Helpers\ResponseError;
use App\Http\Requests\DigitalFile\SellerStoreRequest;
use App\Http\Requests\DigitalFile\SellerUpdateRequest;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Resources\DigitalFileResource;
use App\Models\DigitalFile;
use App\Repositories\DigitalFileRepository\DigitalFileRepository;
use App\Services\DigitalFileService\DigitalFileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DigitalFileController extends SellerBaseController
{

    public function __construct(
        private DigitalFileRepository $repository,
        private DigitalFileService $service
    )
    {
        parent::__construct();
    }

    public function index(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $models = $this->repository->paginate($request->merge(['shop_id' => $this->shop->id])->all());

        return DigitalFileResource::collection($models);
    }

    /**
     * Display the specified resource.
     * @param DigitalFile $digitalFile
     * @return JsonResponse
     */
    public function show(DigitalFile $digitalFile): JsonResponse
    {
        $model = $this->repository->show($digitalFile);

        if ($model->product?->shop_id !== $this->shop->id) {
            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_404,
                'message' => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
            ]);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            DigitalFileResource::make($this->repository->show($digitalFile))
        );
    }

    /**
     * Store a newly created resource in storage.
     * @param SellerStoreRequest $request
     * @return JsonResponse
     */
    public function store(SellerStoreRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->service->create($validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language),
            DigitalFileResource::make(data_get($result, 'data'))
        );
    }

    /**
     * Update a newly created resource in storage.
     */
    public function update(DigitalFile $digitalFile, SellerUpdateRequest $request): JsonResponse
    {
        if ($digitalFile->product?->shop_id !== $this->shop->id) {
            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_404,
                'message' => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
            ]);
        }

        $validated = $request->validated();

        $result = $this->service->update($digitalFile, $validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language),
            DigitalFileResource::make(data_get($result, 'data'))
        );
    }

    /**
     * Update a newly created resource in storage.
     */
    public function changeActive(int $id): JsonResponse
    {
        $result = $this->service->changeActive($id, $this->shop->id);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language),
            DigitalFileResource::make(data_get($result, 'data'))
        );
    }

    /**
     * Remove the specified resource from storage.
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function destroy(FilterParamsRequest $request): JsonResponse
    {
        $result = $this->service->delete($request->input('ids', []), $this->shop->id);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse([
                'code'      => ResponseError::ERROR_404,
                'message'   => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
            ]);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_DELETED, locale: $this->language)
        );
    }

}
