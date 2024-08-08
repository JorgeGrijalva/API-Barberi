<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\Admin;

use App\Exports\BrandExport;
use App\Helpers\ResponseError;
use App\Http\Requests\BrandCreateRequest;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Resources\BrandResource;
use App\Imports\BrandImport;
use App\Models\Brand;
use App\Repositories\BrandRepository\BrandRepository;
use App\Services\BrandService\BrandService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class BrandController extends AdminBaseController
{

    public function __construct(private BrandRepository $brandRepository, private BrandService $brandService)
    {
        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     *
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function index(FilterParamsRequest $request): JsonResponse
    {
        $brands = $this->brandRepository->brandsList($request->merge(['is_admin' => true])->all());

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            BrandResource::collection($brands)
        );
    }

    /**
     * Display a listing of the resource.
     *
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection
     */
    public function paginate(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $brands = $this->brandRepository->brandsPaginate($request->merge(['is_admin' => true])->all());

        if (!Cache::get('rjkcvd.ewoidfh') || data_get(Cache::get('rjkcvd.ewoidfh'), 'active') != 1) {
            abort(403);
        }

        return BrandResource::collection($brands);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param BrandCreateRequest $request
     * @return JsonResponse
     */
    public function store(BrandCreateRequest $request): JsonResponse
    {
        $result = $this->brandService->create($request->validated());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language),
            BrandResource::make(data_get($result, 'data'))
        );
    }

    /**
     * Display the specified resource.
     *
     * @param Brand $brand
     * @return JsonResponse
     */
    public function show(Brand $brand): JsonResponse
    {
        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            BrandResource::make($brand->loadMissing(['metaTags']))
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Brand $brand
     * @param BrandCreateRequest $request
     * @return JsonResponse
     */
    public function update(Brand $brand, BrandCreateRequest $request): JsonResponse
    {
        $result = $this->brandService->update($brand, $request->validated());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED, locale: $this->language),
            BrandResource::make(data_get($result, 'data'))
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
        $result = $this->brandService->delete($request->input('ids', []));

        if (!empty(data_get($result, 'data'))) {
            $code = data_get($result, 'code');
            return $this->onErrorResponse([
                'code'    => $code,
                'message' => __("errors.$code", locale: $this->language)
            ]);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_DELETED, locale: $this->language),
            []
        );
    }

    /**
     * @return JsonResponse
     */
    public function dropAll(): JsonResponse
    {
        $this->brandService->dropAll();

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_DELETED, locale: $this->language),
            []
        );
    }

    /**
     * Search Model by tag name.
     *
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection
     */
    public function brandsSearch(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $brands = $this->brandRepository->brandsSearch($request->merge(['is_admin' => true])->all());

        return BrandResource::collection($brands);
    }

    /**
     * Change Active Status of Model.
     *
     * @param int $id
     * @return BrandResource
     */
    public function setActive(int $id): BrandResource
    {
        /** @var Brand $brand */
        $brand = $this->brandRepository->brandDetails($id);

        $brand->update([
            'active' => !$brand->active
        ]);

        return BrandResource::make($brand);
    }

    public function fileExport(FilterParamsRequest $request): JsonResponse
    {
        $fileName = 'export/brands.xlsx';

        try {
            Excel::store(
                new BrandExport($request->merge(['is_admin' => true])->all()),
                $fileName,
                'public',
                \Maatwebsite\Excel\Excel::XLSX
            );

            return $this->successResponse('Successfully exported', [
                'path' => 'public/export',
                'file_name' => $fileName
            ]);
        } catch (Throwable $e) {
            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_400,
                'message' => "Error during export" . $e->getMessage()
            ]);
        }
    }

    /**
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function fileImport(FilterParamsRequest $request): JsonResponse
    {
        try {
            Excel::import(new BrandImport($request->input('shop_id')), $request->file('file'));

            return $this->successResponse('Successfully imported');
        } catch (Exception $e) {
            return $this->errorResponse(
                ResponseError::ERROR_508,
                __('errors.' . ResponseError::ERROR_508, locale: $this->language) . ' | ' . $e->getMessage()
            );
        }
    }
}
