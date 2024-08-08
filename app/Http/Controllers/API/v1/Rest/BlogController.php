<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Rest;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Resources\BlogResource;
use App\Http\Resources\ReviewResource;
use App\Repositories\BlogRepository\BlogRepository;
use App\Repositories\ReviewRepository\ReviewRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BlogController extends RestBaseController
{

    public function __construct(private BlogRepository $repository)
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
        $blogs = $this->repository->blogsPaginate($request->merge(['published_at' => true])->all());

        return BlogResource::collection($blogs);
    }

    /**
     * Find Blog by UUID.
     *
     * @param string $uuid
     * @return JsonResponse
     */
    public function show(string $uuid): JsonResponse
    {
        $blog = $this->repository->blogByUUID($uuid);

        if (empty($blog)) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        return $this->successResponse(__('errors.'. ResponseError::NO_ERROR), BlogResource::make($blog));
    }

    /**
     * Find Blog by ID.
     *
     * @param int|string $id
     * @return JsonResponse
     */
    public function showById(int|string $id): JsonResponse
    {
        $blog = $this->repository->blogByID($id);

        if (empty($blog)) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        return $this->successResponse(__('errors.'. ResponseError::NO_ERROR), BlogResource::make($blog));
    }

    /**
     * @param int $id
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection
     */
    public function reviews(int $id, FilterParamsRequest $request): AnonymousResourceCollection
    {
        $filter = $request->merge([
            'type'      => 'blog',
            'type_id'   => $id,
        ])->all();

        $result = (new ReviewRepository)->paginate($filter, [
            'user' => fn($q) => $q
                ->select([
                    'id',
                    'uuid',
                    'firstname',
                    'lastname',
                    'password',
                    'img',
                    'active',
                ]),
            'reviewable',
            'galleries'
        ]);

        return ReviewResource::collection($result);
    }


    /**
     * @param int $id
     * @return float[]
     */
    public function reviewsGroupByRating(int $id): array
    {
        return $this->repository->reviewsGroupByRating($id);
    }
}
