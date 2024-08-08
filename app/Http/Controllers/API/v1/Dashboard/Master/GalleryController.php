<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\Master;

use App\Helpers\ResponseError;
use App\Http\Requests\Gallery\StoreRequest;
use App\Http\Resources\GalleryResource;
use App\Http\Resources\UserResource;
use App\Models\Gallery;
use App\Services\GalleryService\FileStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class GalleryController extends MasterBaseController
{
    public function __construct(private FileStorageService $service)
    {
        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     *
     * @return AnonymousResourceCollection
     */
    public function index(): AnonymousResourceCollection
    {
        $models = auth('sanctum')->user()->galleries()->where('type', Gallery::MASTER_GALLERIES)->get();

        return GalleryResource::collection($models);
    }

    /**
     * Display a listing of the resource.
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
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            UserResource::make(data_get($result, 'data'))
        );
    }

}
