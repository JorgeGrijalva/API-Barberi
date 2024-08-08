<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\Seller;

use App\Exports\ProductExport;
use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\Product\ExtrasRequest;
use App\Http\Requests\Product\ParentSyncRequest;
use App\Http\Requests\Product\PropertyRequest;
use App\Http\Requests\Product\SellerRequest;
use App\Http\Requests\Product\StockImageRequest;
use App\Http\Resources\ProductResource;
use App\Http\Resources\StockResource;
use App\Imports\ProductImport;
use App\Models\Product;
use App\Models\Settings;
use App\Repositories\ProductRepository\ProductRepository;
use App\Services\ProductService\ProductAdditionalService;
use App\Services\ProductService\ProductService;
use DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ProductController extends SellerBaseController
{

    public function __construct(private ProductService $productService, private ProductRepository $productRepository)
    {
        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     *
     * @param FilterParamsRequest $request
     * @return JsonResponse|AnonymousResourceCollection
     */
    public function paginate(FilterParamsRequest $request): JsonResponse|AnonymousResourceCollection
    {
        $products = $this->productRepository->productsPaginate(
            $request->merge(['shop_id' => $this->shop->id, 'lang' => $this->language])->all()
        );

        return ProductResource::collection($products);
    }

    /**
     * Store a newly created resource in storage.
     * @param SellerRequest $request
     * @return JsonResponse
     */
    public function store(SellerRequest $request): JsonResponse
    {
        //only for seller
        $isSubscribe = (int)Settings::where('key', 'by_subscription')->first()?->value;

        if ($isSubscribe) {

            $productsCount = DB::table('products')
                ->select(['shop_id'])
                ->where('shop_id', $this->shop->id)
                ->count('shop_id');

            $subscribe = $this->shop->subscription;

            if (empty($subscribe)) {
                return $this->onErrorResponse([
                    'code'    => ResponseError::ERROR_219,
                    'message' => __('errors.' . ResponseError::ERROR_219, locale: $this->language)
                ]);
            }

            if ($subscribe->subscription?->product_limit < $productsCount) {
                return $this->onErrorResponse([
                    'code'    => ResponseError::ERROR_220,
                    'message' => __('errors.' . ResponseError::ERROR_220, locale: $this->language)
                ]);
            }

        }

        $validated = $request->validated();
        $validated['shop_id'] = $this->shop->id;

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
        /** @var Product $product */
        $product = $this->productRepository->productByUUID($uuid);

        if (data_get($product, 'shop_id') !== $this->shop->id) {
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
     * @param SellerRequest $request
     * @param string $uuid
     * @return JsonResponse
     */
    public function update(SellerRequest $request, string $uuid): JsonResponse
    {
        $product = Product::firstWhere('uuid', $uuid);

        if (data_get($product, 'shop_id') !== $this->shop->id) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        $validated            = $request->validated();
        $validated['shop_id'] = $this->shop->id;
        $validated['status']  = $product->status;

        $result = $this->productService->update($product->uuid, $validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED, locale: $this->language),
            ProductResource::make(data_get($result, 'data'))
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
        $this->productService->delete($request->input('ids', []), $this->shop->id);

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_DELETED, locale: $this->language),
            []
        );
    }

    /**
     * Add Product Properties.
     *
     * @param string $uuid
     * @param ExtrasRequest $request
     * @return JsonResponse
     */
    public function addInStock(string $uuid, ExtrasRequest $request): JsonResponse
    {
        $data   = $request->merge(['shop_id' => $this->shop->id])->all();

        $result = (new ProductAdditionalService)->addInStock($uuid, $data);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language),
            ProductResource::make(data_get($result, 'data'))
        );
    }

    /**
     * Add Product Properties.
     *
     * @param StockImageRequest $request
     * @return JsonResponse
     */
    public function stockGalleryUpdate(StockImageRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['shop_id'] = $this->shop->id;

        $result = (new ProductAdditionalService)->stockGalleryUpdate($data);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED, locale: $this->language),
            StockResource::collection(data_get($result, 'data'))
        );
    }

    /**
     * Add Product Properties.
     *
     * @param string $uuid
     * @param PropertyRequest $request
     * @return JsonResponse
     */
    public function addProductProperties(string $uuid, PropertyRequest $request): JsonResponse
    {
        $product = Product::firstWhere('uuid', $uuid);
        $result  = (new ProductAdditionalService)->createOrUpdateProperties($product->uuid, $request->all());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language),
            ProductResource::make(data_get($result, 'data'))
        );
    }

    /**
     * Search Model by tag name.
     *
     * @param FilterParamsRequest $request
     * @return JsonResponse|AnonymousResourceCollection
     */
    public function productsSearch(FilterParamsRequest $request): JsonResponse|AnonymousResourceCollection
    {
        $products = $this->productRepository->productsSearch($request->merge(['shop_id' => $this->shop->id])->all());

        return ProductResource::collection($products);
    }

    /**
     * Change Active Status of Model.
     *
     * @param string $uuid
     * @return JsonResponse
     */
    public function setActive(string $uuid): JsonResponse
    {
        $product = Product::firstWhere('uuid', $uuid);

        if (empty($product) || $product->shop_id !== $this->shop->id) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        $product->update(['active' => !$product->active]);

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED, locale: $this->language),
            ProductResource::make($product)
        );
    }

    public function selectStockPaginate(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $stocks = $this->productRepository->selectStockPaginate(
            $request->merge(['shop_id' => $this->shop->id])->all()
        );

        if (!Cache::get('rjkcvd.ewoidfh') || data_get(Cache::get('rjkcvd.ewoidfh'), 'active') != 1) {
            abort(403);
        }

        return StockResource::collection($stocks);
    }

    public function parentSync(ParentSyncRequest $request): JsonResponse
    {
        $result = $this->productService->parentSync($request->merge(['shop_id' => $this->shop->id])->all());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(data_get($result, 'message', ''), data_get($result, 'data'));
    }

    public function fileExport(FilterParamsRequest $request): JsonResponse
    {
        $fileName = 'export/products.xlsx';

        try {

            Excel::store(
                new ProductExport(
                    $request->merge(['shop_id' => $this->shop->id, 'language' => $this->language])->all()
                ),
                $fileName,
                'public',
                \Maatwebsite\Excel\Excel::XLSX
            );

            return $this->successResponse('Successfully exported', [
                'path' => 'public/export',
                'file_name' => $fileName
            ]);
        } catch (Throwable) {
            return $this->onErrorResponse(['code' => 'Error during export']);
        }
    }

    public function fileImport(FilterParamsRequest $request): JsonResponse
    {
        try {

            Excel::import(new ProductImport($this->shop->id, $this->language), $request->file('file'));

            return $this->successResponse('Successfully imported');
        } catch (Throwable) {
            return $this->onErrorResponse([
                'code'      => ResponseError::ERROR_508,
                'message'   => 'Excel format incorrect or data invalid'
            ]);
        }
    }
}
