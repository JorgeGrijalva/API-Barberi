<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\Seller;

use App\Exports\CategoryExport;
use App\Helpers\ResponseError;
use App\Http\Requests\CategoryCreateRequest;
use App\Http\Requests\CategoryFilterRequest;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Resources\CategoryResource;
use App\Imports\CategoryImport;
use App\Models\Category;
use App\Repositories\CategoryRepository\CategoryRepository;
use App\Services\CategoryServices\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class CategoryController extends SellerBaseController
{
    
    private array $types = ['main', 'sub_main', 'child', 'receipt'];

    public function __construct(private CategoryService $service, private CategoryRepository $repository)
    {
        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     *
     * @param CategoryFilterRequest $request
     * @return JsonResponse|AnonymousResourceCollection
     */
    public function index(CategoryFilterRequest $request): JsonResponse|AnonymousResourceCollection
    {
        $filter = $request->all();

        if (in_array(data_get($filter, 'type'), $this->types)) {
            $filter['shop_id'] = $this->shop->id;
        }

        $categories = $this->repository->categories($filter);

        return CategoryResource::collection($categories);
    }

    /**
     * Display a listing of the resource with paginate.
     *
     * @param CategoryFilterRequest $request
     * @return JsonResponse|AnonymousResourceCollection
     */
    public function paginate(CategoryFilterRequest $request): JsonResponse|AnonymousResourceCollection
    {
        $filter = $request->all();

        if (in_array(data_get($filter, 'type'), $this->types)) {
            $filter['shop_id'] = $this->shop->id;
        }

        $categories = $this->repository->parentCategories($filter);

        return CategoryResource::collection($categories);
    }

    /**
     * Display a listing of the resource with paginate.
     *
     * @param CategoryFilterRequest $request
     * @return JsonResponse|AnonymousResourceCollection
     */
    public function selectPaginate(CategoryFilterRequest $request): JsonResponse|AnonymousResourceCollection
    {
        $filter = $request->all();

        if (in_array(data_get($filter, 'type'), $this->types)) {
            $filter['shop_id'] = $this->shop->id;
        }

        $categories = $this->repository->selectPaginate($filter);

        return CategoryResource::collection($categories);
    }

    /**
     * Display a listing of the resource with paginate.
     *
     * @param CategoryFilterRequest $request
     * @return JsonResponse|AnonymousResourceCollection
     */
    public function mySelectPaginate(CategoryFilterRequest $request): JsonResponse|AnonymousResourceCollection
    {
        $filter = $request->all();

        if (in_array(data_get($filter, 'type'), $this->types)) {
            $filter['shop_id'] = $this->shop->id;
        }

        $categories = $this->repository->mySelectPaginate($filter);

        return CategoryResource::collection($categories);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param CategoryCreateRequest $request
     * @return JsonResponse
     */
    public function store(CategoryCreateRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['shop_id'] = $this->shop->id;

        $result = $this->service->create($validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language)
        );
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $uuid
     * @return JsonResponse
     */
    public function show(string $uuid): JsonResponse
    {
        $category = $this->repository->categoryByUuid($uuid);

        if (!$category) {
            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_404,
                'message' => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
            ]);
        }

        /** @var Category $category */
        if (!empty($category->shop_id) && $category->shop_id !== $this->shop->id) {
            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_404,
                'message' => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
            ]);
        }

        $category->load([
            'translations',
            'metaTags',
        ]);

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            CategoryResource::make($category)
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param CategoryCreateRequest $request
     * @param string $uuid
     * @return JsonResponse
     */
    public function update(string $uuid, CategoryCreateRequest $request): JsonResponse
    {
        $category = Category::where('uuid', $uuid)->first();

        if (!$category || $category->shop_id !== $this->shop->id) {
            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_404,
                'message' => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
            ]);
        }

        $result = $this->service->update($category->uuid, $request->validated());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(__('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED));
    }

    /**
     * Remove Model image from storage.
     *
     * @param string $uuid
     * @return JsonResponse
     */
    public function imageDelete(string $uuid): JsonResponse
    {
        $category = Category::firstWhere('uuid', $uuid);

        if (!$category || $category->shop_id !== $this->shop->id) {
            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_404,
                'message' => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
            ]);
        }

        $category->galleries()->where('path', $category->img)->delete();

        $category->update(['img' => null]);

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_DELETED, locale: $this->language),
            $category
        );
    }

    /**
     * Search Model by tag name.
     *
     * @param CategoryFilterRequest $request
     * @return JsonResponse|AnonymousResourceCollection
     */
    public function categoriesSearch(CategoryFilterRequest $request): JsonResponse|AnonymousResourceCollection
    {
        $categories = $this->repository->categoriesSearch($request->merge(['shop_id' => $this->shop->id])->all());

        return CategoryResource::collection($categories);
    }

    public function fileExport(FilterParamsRequest $request): JsonResponse
    {
        $fileName = 'export/categories.xlsx';

        try {
            Excel::store(
                new CategoryExport($this->language, $request->merge(['shop_id' => $this->shop->id])->all()),
                $fileName,
                'public',
                \Maatwebsite\Excel\Excel::XLSX
            );
        } catch (Throwable $e) {
            $this->error($e);
            return $this->errorResponse('Error during export');
        }

        return $this->successResponse('Successfully exported', [
            'path'      => 'public/export',
            'file_name' => $fileName
        ]);
    }

    public function fileImport(Request $request): JsonResponse
    {
        try {
            Excel::import(new CategoryImport($this->language, $this->shop->id), $request->file('file'));

            return $this->successResponse('Successfully imported');
        } catch (Throwable $e) {
            $this->error($e);
            return $this->errorResponse(
                ResponseError::ERROR_508,
                __('errors.' . ResponseError::ERROR_508, locale: $this->language) . ' | ' . $e->getMessage()
            );
        }
    }

    /**
     * @param string $uuid
     * @return JsonResponse
     */
    public function changeActive(string $uuid): JsonResponse
    {
        $result = $this->service->changeActive($uuid, $this->shop->id);

        if (!empty(data_get($result, 'data'))) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(__('errors.' . ResponseError::ERROR_502, locale: $this->language));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function destroy(FilterParamsRequest $request): JsonResponse
    {
        $result = $this->service->delete($request->input('ids', []), $this->shop->id);

        if (!empty(data_get($result, 'data'))) {
            return $this->onErrorResponse([
                'code'      => ResponseError::ERROR_504,
                'message'   => 'Can`t delete record that has children or products.'
            ]);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_DELETED, locale: $this->language),
            []
        );
    }
}
