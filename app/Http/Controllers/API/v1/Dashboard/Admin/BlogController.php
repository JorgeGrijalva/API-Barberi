<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\Admin;

use App\Helpers\ResponseError;
use App\Http\Requests\Blog\AdminRequest;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Resources\BlogResource;
use App\Models\Blog;
use App\Models\Language;
use App\Models\PushNotification;
use App\Repositories\BlogRepository\BlogRepository;
use App\Services\BlogService\BlogService;
use App\Traits\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BlogController extends AdminBaseController
{
    use Notification;

    public function __construct(private BlogRepository $repository, private BlogService $service)
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
        $blogs = $this->repository->blogsPaginate($request->all());

        return BlogResource::collection($blogs);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param AdminRequest $request
     * @return JsonResponse
     */
    public function store(AdminRequest $request): JsonResponse
    {
        $result = $this->service->create($request->validated());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language),
            []
        );
    }

    /**
     * Display the specified resource.
     *
     * @param int|string $uuid
     * @return JsonResponse
     */
    public function show(int|string $uuid): JsonResponse
    {
        $blog = $this->repository->blogByID($uuid);

        if (empty($blog)) {
            $blog = $this->repository->blogByUUID($uuid);
        }

        if (empty($blog)) {
            return $this->onErrorResponse([
                'code'      => ResponseError::ERROR_404,
                'message'   => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
            ]);
        }

        /** @var Blog $blog */
        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            BlogResource::make($blog->loadMissing('translations'))
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param string $uuid
     * @param AdminRequest $request
     * @return JsonResponse
     */
    public function update(string $uuid, AdminRequest $request): JsonResponse
    {
        $result = $this->service->update($uuid, $request->validated());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED, locale: $this->language),
            []
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
     * Change Active Status of Mode
     *
     * @param string $uuid
     * @return JsonResponse
     */
    public function setActiveStatus(string $uuid): JsonResponse
    {
        $locale = Language::where('default', 1)->first()?->locale;

        $blog = Blog::with([
            'translation' => fn($query) => $query->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                }))
        ])
            ->firstWhere('uuid', $uuid);

        if (empty($blog)) {
            return $this->onErrorResponse([
                'code'      => ResponseError::ERROR_404,
                'message'   => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
            ]);
        }

        /** @var Blog $blog */
        $result = $this->service->setActiveStatus($blog);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED, locale: $this->language),
            []
        );
    }

    /**
     * @param string $uuid
     * @return JsonResponse
     */
    public function blogPublish(string $uuid): JsonResponse
    {
        $blog = Blog::with([
            'translations'
        ])
            ->firstWhere('uuid', $uuid);

        if (empty($blog)) {
            return $this->onErrorResponse([
                'code'      => ResponseError::ERROR_404,
                'message'   => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
            ]);
        }

        /** @var Blog $blog */
        $result = $this->service->blogPublish($blog);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        dispatch(function () use ($blog) {
            $this->sendAllNotification(
                $blog,
                [
                    'id'            => $blog->id,
                    'published_at'  => optional($blog->published_at)->format('Y-m-d H:i:s'),
                    'type'          => $blog->type === 'blog' ? PushNotification::NEWS_PUBLISH : PushNotification::NOTIFICATIONS
                ],
            );
        })->afterResponse();

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED, locale: $this->language),
            []
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
}
