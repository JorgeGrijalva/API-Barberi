<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Rest;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\Order\StocksCalculateRequest;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ReviewResource;
use App\Jobs\UserActivityJob;
use App\Models\Category;
use App\Models\Point;
use App\Models\Product;
use App\Models\Shop;
use App\Repositories\CategoryRepository\CategoryRepository;
use App\Repositories\OrderRepository\OrderReportRepository;
use App\Repositories\ProductRepository\ProductReportRepository;
use App\Repositories\ProductRepository\ProductRepository;
use App\Repositories\ProductRepository\RestProductRepository;
use App\Repositories\ShopRepository\ShopRepository;
use App\Services\ProductService\ProductReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection;

class ProductController extends RestBaseController
{

    public function __construct(
        private ProductRepository $productRepository,
        private RestProductRepository $restProductRepository,
        private ProductReportRepository $productReportRepository,
    )
    {
        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     *
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection
     */
    public function paginate(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $products = $this->restProductRepository->productsPaginate(
            $request->merge([
                'status'      => Product::PUBLISHED,
                'shop_status' => Shop::APPROVED
            ])->all()
        );

        return ProductResource::collection($products);
    }

    /**
     * Display a listing of the resource.
     *
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection
     */
    public function adsPaginate(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $products = $this->restProductRepository->adsPaginate(
            $request->merge([
                'rest'   => true,
                'status' => Product::PUBLISHED,
                'active' => 1,
            ])->all()
        );

        return ProductResource::collection($products);
    }

    /**
     * Display the specified resource.
     *
     * @param string $uuid
     * @return JsonResponse
     */
    public function show(string $uuid): JsonResponse
    {
        $product = $this->restProductRepository->productByUUID($uuid);

        if (!data_get($product, 'id')) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        UserActivityJob::dispatchAfterResponse(
            $product->id,
            get_class($product),
            'click',
            1,
            auth('sanctum')->user()
        );

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            ProductResource::make($product)
        );
    }

    /**
     * Display the specified resource.
     *
     * @param string $slug
     * @return JsonResponse
     */
    public function showSlug(string $slug): JsonResponse
    {
        $product = $this->restProductRepository->productBySlug($slug);

        if (!data_get($product, 'id')) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        UserActivityJob::dispatchAfterResponse(
            $product->id,
            get_class($product),
            'click',
            1,
            auth('sanctum')->user()
        );

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            ProductResource::make($product)
        );
    }

    /**
     * Display the specified resource.
     *
     * @param int|string $id
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection
     */
    public function alsoBought(int|string $id, FilterParamsRequest $request): AnonymousResourceCollection
    {
        $products = $this->restProductRepository->alsoBought((int)$id, $request->all());

        return ProductResource::collection($products);
    }

    /**
     * Display the specified resource.
     * @param string $uuid
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection
     */
    public function related(string $uuid, FilterParamsRequest $request): AnonymousResourceCollection
    {
        $models = $this->restProductRepository->related($uuid, $request->all());

        return ProductResource::collection($models);
    }

    /**
     * @param int $id
     * @return float[]
     */
    public function reviewsGroupByRating(int $id): array
    {
        return $this->restProductRepository->reviewsGroupByRating($id);
    }

    public function productsByShopUuid(string $uuid): JsonResponse|AnonymousResourceCollection
    {
        /** @var Shop $shop */
        $shop = (new ShopRepository)->shopDetails($uuid);

        if (!data_get($shop, 'id')) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        $products = $this->restProductRepository->productsPaginate(
            ['shop_id' => $shop->id, 'rest' => true, 'status' => Product::PUBLISHED, 'active' => 1]
        );

        return ProductResource::collection($products);
    }

    public function productsByBrand(int $id): AnonymousResourceCollection
    {
        $products = $this->restProductRepository->productsPaginate(
            ['brand_id' => $id, 'rest' => true, 'status' => Product::PUBLISHED, 'active' => 1]
        );

        return ProductResource::collection($products);
    }

    public function productsByCategoryUuid(string $uuid): JsonResponse|AnonymousResourceCollection
    {
        $category = (new CategoryRepository)->categoryByUuid($uuid);

        if (!$category && data_get($category, 'type') !== Category::MAIN) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        $products = $this->restProductRepository->productsPaginate(
            ['category_id' => $category->id, 'rest' => true, 'status' => Product::PUBLISHED, 'active' => 1]
        );

        return ProductResource::collection($products);
    }

    /**
     * Search Model by tag name.
     *
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection
     */
    public function productsSearch(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $products = $this->productRepository->productsSearch(
            $request->merge(['status' => Product::PUBLISHED, 'active' => 1])->all(),
        );

        return ProductResource::collection($products);
    }

    /**
     * Search Model by tag name.
     *
     * @param string $uuid
     * @return JsonResponse
     */
    public function reviews(string $uuid): JsonResponse
    {
        $result = (new ProductReviewService)->reviews($uuid);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        return $this->successResponse(
            ResponseError::NO_ERROR,
            ReviewResource::collection(data_get($result, 'data'))
        );
    }

    /**
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection
     */
    public function discountProducts(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $products = $this->restProductRepository->productsDiscount(
            $request->merge(['status' => Product::PUBLISHED])->all()
        );

        return ProductResource::collection($products);
    }

    /**
     * @param StocksCalculateRequest $request
     * @return JsonResponse
     */
    public function orderStocksCalculate(StocksCalculateRequest $request): JsonResponse
    {
        $result = (new OrderReportRepository)->orderStocksCalculate($request->validated());

        return $this->successResponse(__('errors.' . ResponseError::NO_ERROR, locale: $this->language), $result);
    }

    /**
     * Get Products by IDs.
     *
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection
     */
    public function productsByIDs(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $products = $this->restProductRepository->productsByIDs($request->all());

        return ProductResource::collection($products);
    }

    /**
     * Compare products.
     *
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection|array
     */
    public function compare(FilterParamsRequest $request): Collection|array
    {
        $ids = $request->input('ids');

        if (empty($ids) || !is_array($ids) || count($ids) == 0) {
            return [
                'data' => []
            ];
        }

        return [
            'data' => $this->restProductRepository->compare($request->all())
        ];
    }

    public function checkCashback(FilterParamsRequest $request): JsonResponse
    {
        $point = Point::getActualPoint($request->input('amount', 0));

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            ['price' => $point]
        );
    }

    public function history(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $filter  = $request->merge(['user_id' => auth('sanctum')->id()])->all();

        $history = $this->productReportRepository->history($filter);

        return ProductResource::collection($history);
    }
}
