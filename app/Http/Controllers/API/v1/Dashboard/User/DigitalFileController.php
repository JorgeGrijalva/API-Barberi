<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\User;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Resources\DigitalFileResource;
use App\Http\Resources\UserDigitalFileResource;
use App\Models\UserDigitalFile;
use App\Repositories\DigitalFileRepository\DigitalFileRepository;
use App\Services\DigitalFileService\DigitalFileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class DigitalFileController extends UserBaseController
{
    public function __construct(private DigitalFileRepository $repository, private DigitalFileService $service)
    {
        parent::__construct();
    }

    /**
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection
     */
    public function index(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $models = $this->repository->paginate($request->all());

        return DigitalFileResource::collection($models);
    }

    /**
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection
     */
    public function myDigitalFiles(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $filter = $request->merge(['user_id' => auth('sanctum')->id()])->all();

        $models = $this->repository->myDigitalFile($filter);

        return UserDigitalFileResource::collection($models);
    }

    /**
     * @param int $id
     * @return JsonResponse|BinaryFileResponse
     */
    public function getDigitalFile(int $id): BinaryFileResponse|JsonResponse
    {
        /** @var UserDigitalFile $model */
        $model = $this->repository->getDigitalFile($id);

        $result = $this->service->getDigitalFile($model);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        try {
            return response()->download(data_get($result, 'data'), 'file-' . time());
        } catch (Throwable) {
            return $this->onErrorResponse([
                'status'  => false,
                'code'    => ResponseError::ERROR_404,
                'message' => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
            ]);
        }
    }
}
