<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Rest;

use App\Helpers\ResponseError;
use App\Http\Requests\Booking\StoreRequest;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\MasterDisabledTime\MultiTimeFilterRequest;
use App\Http\Requests\MasterDisabledTime\TimeFilterRequest;
use App\Http\Resources\GalleryResource;
use App\Http\Resources\UserResource;
use App\Models\Gallery;
use App\Models\User;
use App\Repositories\BookingRepository\BookingRepository;
use App\Repositories\UserRepository\MasterRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Throwable;

class MasterController extends RestBaseController
{
    public function __construct(private MasterRepository $repository)
    {
        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     *
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection
     */
    public function index(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $models = $this->repository->index($request->all());

        return UserResource::collection($models);
    }

    /**
     * Display the specified resource.
     *
     * @param User $master
     * @return JsonResponse
     */
    public function show(User $master): JsonResponse
    {
        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            UserResource::make($this->repository->show($master))
        );
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @param TimeFilterRequest $request
     * @return JsonResponse
     */
    public function times(int $id, TimeFilterRequest $request): JsonResponse
    {
        try {
            return $this->successResponse(
                __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
                $this->repository->times($id, $request->all())
            );
        } catch (Throwable $e) {
            return $this->onErrorResponse([
                'message' => $e->getMessage() . ' ' . $e->getLine()  . ' ' . $e->getFile()
            ]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param MultiTimeFilterRequest $request
     * @return JsonResponse
     */
    public function timesAll(MultiTimeFilterRequest $request): JsonResponse
    {
        try {
            return $this->successResponse(
                __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
                $this->repository->timesAll($request->validated())
            );
        } catch (Throwable $e) {
            return $this->onErrorResponse([
                'message' => $e->getMessage() . ' ' . $e->getLine()  . ' ' . $e->getFile()
            ]);
        }
    }

    /**
     * @param int $id
     * @return AnonymousResourceCollection
     */
    public function galleries(int $id): AnonymousResourceCollection
    {
        /** @var User $master */
        $master = User::with([
            'galleries' => fn($q) => $q->where('type', Gallery::MASTER_GALLERIES),
        ])
            ->whereHas('roles', fn($q) => $q->where('name', 'master'))
            ->where('id', $id)
            ->first();

        return GalleryResource::collection($master?->galleries);
    }

    /**
     * Display the specified resource.
     *
     * @param StoreRequest $request
     * @return JsonResponse
     */
    public function calculate(StoreRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = (new BookingRepository)->calculate($validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(__('errors.' . ResponseError::NO_ERROR, locale: $this->language), $result);
    }
}
