<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1;

use App\Helpers\FileHelper;
use App\Helpers\ResponseError;
use App\Http\Controllers\Controller;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\GalleryMultiUploadRequest;
use App\Http\Requests\GalleryUploadRequest;
use App\Http\Resources\GalleryResource;
use App\Models\Gallery;
use App\Services\GalleryService\FileStorageService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class GalleryController extends Controller
{
    use ApiResponse;

    private Gallery $model;

    public function __construct(Gallery $model, private FileStorageService $storageService)
    {
        parent::__construct();

        $this->middleware(['sanctum.check'])->except('store');

        $this->model = $model;
    }

    /**
     * Display a listing of the resource.
     *
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function getStorageFiles(FilterParamsRequest $request): JsonResponse
    {
        $type = $request->input('type');

        if (!in_array($type, Gallery::TYPES) || $type === 'chats') {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_432]);
        }

        $files = $this->storageService->getStorageFiles($request->all());

        return $this->successResponse(__('errors.' . ResponseError::NO_ERROR, locale: $this->language), $files);
    }

    /**
     * Destroy a file from the storage.
     *
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function deleteStorageFile(FilterParamsRequest $request): JsonResponse
    {
        if (!is_array($request->input('ids'))) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        $result = $this->storageService->deleteFileFromStorage($request->all());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            data_get($result, 'data')
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
        $galleries = $this->model
            ->filter($request->all())
            ->where('type', '!=', 'chats')
            ->paginate($request->input('perPage', 15));

        return GalleryResource::collection($galleries);
    }

    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function types(): JsonResponse
    {
        $types = Gallery::TYPES;

        unset($types[Gallery::CHATS]);

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            $types
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param GalleryUploadRequest $request
     * @return JsonResponse
     */
    public function store(GalleryUploadRequest $request): JsonResponse
    {
        if (!$request->file('image')) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        $result = FileHelper::uploadFile($request->file('image'), $request->input('type', Gallery::OTHER));

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            [
                'title' => data_get($result, 'data'),
                'type'  => $request->input('type')
            ]
        );

    }

    /**
     * Store multiple newly created resources in storage
     *
     * @param GalleryMultiUploadRequest $request
     * @return JsonResponse
     */
    public function storeMany(GalleryMultiUploadRequest $request): JsonResponse
    {
        if (!$request->file('images')) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        $images = $request->file(['images']);
        $result = [];

        foreach ($images as $image) {
            $result[] = FileHelper::uploadFile($image, $request->input('type', Gallery::OTHER));
        }

        $titles = [];

        foreach ($result as $item) {

            if (!data_get($item, 'status')) {
                return $this->onErrorResponse($item);
            }

            $titles[] = data_get($item, 'data');
        }

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            [
                'title' => $titles,
                'type'  => $request->input('type')
            ]
        );
    }
}
