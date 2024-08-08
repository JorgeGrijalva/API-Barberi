<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\Admin;

use App\Exports\ShopExport;
use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\Shop\ImageDeleteRequest;
use App\Http\Requests\Shop\StoreRequest;
use App\Http\Requests\Shop\ShopStatusChangeRequest;
use App\Http\Resources\ShopResource;
use App\Imports\ShopImport;
use App\Models\Shop;
use App\Models\User;
use App\Repositories\ShopRepository\AdminShopRepository;
use App\Services\ShopServices\ShopActivityService;
use App\Services\ShopServices\ShopService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ShopController extends AdminBaseController
{

    public function __construct(private ShopService $service, private AdminShopRepository $repository)
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
        $shops = $this->repository->shopsList($request->all());

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            ShopResource::collection($shops)
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
        $shops = $this->repository->shopsPaginate($request->all());

        return ShopResource::collection($shops);
    }

    /**
     * Shop a newly created.
     *
     * @param StoreRequest $request
     * @return JsonResponse
     */
    public function store(StoreRequest $request): JsonResponse
    {
        $result = $this->service->create($request->all());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language),
            ShopResource::make(data_get($result, 'data'))
        );
    }

    /**
     * Display the specified resource.
     *
     * @param string $uuid
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function show(string $uuid, FilterParamsRequest $request): JsonResponse
    {
        $shop = $this->repository->shopDetails($uuid, $request->all());

        if (empty($shop)) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        /** @var Shop $shop */
        $shop->loadMissing('translations');

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            ShopResource::make($shop)
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param StoreRequest $request
     * @param string $uuid
     * @return JsonResponse
     */
    public function update(StoreRequest $request, string $uuid): JsonResponse
    {
        $result = $this->service->update($uuid, $request->all());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED, locale: $this->language),
            ShopResource::make(data_get($result, 'data'))
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
        $this->service->delete($request->input('ids', []));

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_DELETED, locale: $this->language),
            []
        );
    }

    /**
     * @return JsonResponse
     * @throws Exception
     */
    public function setWorkingStatus(): JsonResponse
    {
        /** @var User $user */
        $user = auth('sanctum')->user();

        (new ShopActivityService)->changeOpenStatus($user?->shop?->uuid);

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            ShopResource::make($user?->shop)
        );
    }

    public function dropAll(): JsonResponse
    {
        $this->service->dropAll();

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_DELETED, locale: $this->language),
            []
        );
    }

    /**
     * Search shop Model from database.
     *
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection
     */
    public function shopsSearch(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $categories = $this->repository->shopsSearch($request->all());

        return ShopResource::collection($categories);
    }

    /**
     * Remove Model image from storage.
     *
     * @param ImageDeleteRequest $request
     * @param string $uuid
     * @return JsonResponse
     */
    public function imageDelete(ImageDeleteRequest $request, string $uuid): JsonResponse
    {
        $result = $this->service->imageDelete($uuid, $request->validated());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_DELETED, locale: $this->language),
            data_get($result, 'shop')
        );
    }

    /**
     * Change Shop Status.
     *
     * @param string $uuid
     * @param ShopStatusChangeRequest $request
     * @return JsonResponse
     */
    public function statusChange(string $uuid, ShopStatusChangeRequest $request): JsonResponse
    {
        $result = (new ShopActivityService)->changeStatus($uuid, $request->input('status'));

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED, locale: $this->language),
            []
        );
    }

    public function fileExport(): JsonResponse
    {
        $fileName = 'export/shops.xlsx';

        try {
            Excel::store(new ShopExport, $fileName, 'public', \Maatwebsite\Excel\Excel::XLSX);

            return $this->successResponse('Successfully exported', [
                'path' => 'public/export',
                'file_name' => $fileName
            ]);
        } catch (Throwable) {
            return $this->errorResponse('Error during export');
        }
    }

    public function fileImport(FilterParamsRequest $request): JsonResponse
    {
        try {
            Excel::import(new ShopImport, $request->file('file'));

            return $this->successResponse('Successfully imported');
        } catch (Throwable $e) {
            $this->error($e);
            return $this->errorResponse(
                ResponseError::ERROR_508,
                'Excel format incorrect or data invalid ' . $e->getMessage()
            );
        }
    }

    /**
     * Change Verify status of model
     *
     * @param int|string $uuid
     * @return JsonResponse
     */
    public function setVerify(int|string $uuid): JsonResponse
    {
        $result = $this->service->updateVerify($uuid);

        if (!$result['status']) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED, locale: $this->language),
            ShopResource::make($result['data'])
        );
    }
}
