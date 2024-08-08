<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\Admin;

use App\Exports\ProductExport;
use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\Product\AdminRequest;
use App\Http\Requests\Product\ExtrasRequest;
use App\Http\Requests\Product\StatusRequest;
use App\Http\Requests\Product\StockImageRequest;
use App\Http\Resources\ProductResource;
use App\Http\Resources\StockResource;
use App\Http\Resources\UserActivityResource;
use App\Imports\ProductImport;
use App\Models\Shop;
use App\Repositories\ProductRepository\ProductReportRepository;
use App\Repositories\ProductRepository\ProductRepository;
use App\Services\ProductService\ProductAdditionalService;
use App\Services\ProductService\ProductService;
use App\Services\ProductService\StockService;
use App\Traits\Loggable;
use Exception;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ProductController extends AdminBaseController
{
    use Loggable;

    public function __construct(
        private ProductService $productService,
        private ProductRepository $productRepository,
        private ProductReportRepository $productReportRepository,
    )
    {
        parent::__construct();
    }

    public function paginate(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $products = $this->productRepository->productsPaginate($request->all());

        return ProductResource::collection($products);
    }

    /**
     * Store a newly created resource in storage.
     * @param AdminRequest $request
     * @return JsonResponse
     */
    public function store(AdminRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->productService->create($validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language),
            ProductResource::make(data_get($result, 'data'))
        );
    }

    /**
     * Display the specified resource.
     *
     * @param string $uuid
     * @return JsonResponse
     */
    public function show(string $uuid): JsonResponse
    {
        $product = $this->productRepository->productByUUID($uuid);

        if (empty($product)) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            ProductResource::make($product->loadMissing(['translations', 'metaTags']))
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param AdminRequest $request
     * @param string $uuid
     * @return JsonResponse
     */
    public function update(AdminRequest $request, string $uuid): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->productService->update($uuid, $validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED, locale: $this->language),
            ProductResource::make(data_get($result, 'data'))
        );
    }

    /**
     * @param FilterParamsRequest $request
     * @return JsonResponse|AnonymousResourceCollection
     */
    public function selectStockPaginate(FilterParamsRequest $request): JsonResponse|AnonymousResourceCollection
    {
        $shop = Shop::find((int)$request->input('shop_id'));

        if (!$shop?->id) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_400]);
        }

        $stocks = $this->productRepository->selectStockPaginate(
            $request->merge(['shop_id' => $shop->id])->all()
        );

        return StockResource::collection($stocks);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function destroy(FilterParamsRequest $request): JsonResponse
    {
        $result = $this->productService->delete($request->input('ids', []));

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
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
        $this->productService->dropAll();

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_DELETED, locale: $this->language),
            []
        );
    }

    /**
     * @return JsonResponse
     */
    public function dropAllStocks(): JsonResponse
    {
        (new StockService)->dropAll();

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_DELETED, locale: $this->language)
        );
    }

    /**
     * Add Product Properties.
     *
     * @param string $uuid
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function addProductProperties(string $uuid, FilterParamsRequest $request): JsonResponse
    {
        $result = (new ProductAdditionalService)->createOrUpdateProperties($uuid, $request->all());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language),
            ProductResource::make(data_get($result, 'data'))
        );
    }

    /**
     * Add Product stocks.
     *
     * @param string $uuid
     * @param ExtrasRequest $request
     * @return JsonResponse
     */
    public function addInStock(string $uuid, ExtrasRequest $request): JsonResponse
    {
        $result = (new ProductAdditionalService)->addInStock($uuid, $request->all());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language),
            ProductResource::make(data_get($result, 'data'))
        );
    }

    /**
     * Stock image update.
     *
     * @param StockImageRequest $request
     * @return JsonResponse
     */
    public function stockGalleryUpdate(StockImageRequest $request): JsonResponse
    {
        $result = (new ProductAdditionalService)->stockGalleryUpdate($request->validated());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED, locale: $this->language),
            StockResource::collection(data_get($result, 'data'))
        );
    }

    /**
     * Search Model by tag name.
     *
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection
     */
    public function productsSearch(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $categories = $this->productRepository->productsSearch($request->merge(['visibility' => true])->all());

        return ProductResource::collection($categories);
    }

    /**
     * Change Active Status of Model.
     *
     * @param string $uuid
     * @return JsonResponse
     */
    public function setActive(string $uuid): JsonResponse
    {
        $product = $this->productRepository->productByUUID($uuid);

        if (empty($product)) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        $product->update(['active' => !$product->active]);

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED, locale: $this->language),
            ProductResource::make($product)
        );
    }

    /**
     * Change Active Status of Model.
     *
     * @param string $uuid
     * @param StatusRequest $request
     * @return JsonResponse
     */
    public function setStatus(string $uuid, StatusRequest $request): JsonResponse
    {
        $result = $this->productService->setStatus($uuid, $request->validated());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language),
            data_get($result, 'data')
        );
    }

    public function fileExport(FilterParamsRequest $request): JsonResponse
    {
        $fileName = 'export/products.xlsx';

        $productExport = new ProductExport($request->merge(['language' => $this->language])->all());

        try {
            Excel::store($productExport, $fileName, 'public', \Maatwebsite\Excel\Excel::XLSX);

            return $this->successResponse(__('errors.' . ResponseError::NO_ERROR, locale: $this->language), [
                'path'      => 'public/export',
                'file_name' => $fileName
            ]);
        } catch (Throwable $e) {

            $this->error($e);

            return $this->errorResponse(ResponseError::ERROR_400, $e->getMessage());
        }
    }

    public function fileImport(FilterParamsRequest $request): JsonResponse
    {
        $shopId = $request->input('shop_id');

        try {
            Excel::import(new ProductImport($shopId, $this->language), $request->file('file'));
            return $this->successResponse(__('errors.' . ResponseError::NO_ERROR, locale: $this->language));
        } catch (Exception $e) {
            return $this->onErrorResponse([
                'code'  => ResponseError::ERROR_508,
                'data'  => $e->getMessage()
            ]);
        }
    }

    public function reportPaginate(FilterParamsRequest $request): Paginator|array
    {
        try {
            return $this->productReportRepository->productReportPaginate($request->all());
        } catch (Exception $exception) {
            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_400,
                'message' => $exception->getMessage()
            ];
        }
    }

    public function stockReportPaginate(FilterParamsRequest $request): JsonResponse
    {
        try {
            $result = $this->productReportRepository->stockReportPaginate($request->all());

            return $this->successResponse(
                __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
                $result
            );
        } catch (Exception $exception) {
            return $this->errorResponse(ResponseError::ERROR_400, $exception->getMessage());
        }
    }

    public function history(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $history = $this->productReportRepository->history($request->all());

        return ProductResource::collection($history);
    }

    public function mostPopulars(FilterParamsRequest $request): AnonymousResourceCollection
    {
        return UserActivityResource::collection($this->productReportRepository->mostPopulars($request->all()));
    }
}
