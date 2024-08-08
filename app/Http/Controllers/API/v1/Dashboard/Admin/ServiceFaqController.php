<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\Admin;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\ServiceFaq\StoreRequest;
use App\Http\Resources\ServiceFaqResource;
use App\Models\ServiceFaq;
use App\Repositories\ServiceFaqRepository\ServiceFaqRepository;
use App\Services\ServiceFaqService\ServiceFaqService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ServiceFaqController extends AdminBaseController
{
    public function __construct(private ServiceFaqService $service, private ServiceFaqRepository $repository)
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
        $serviceFaqs = $this->repository->paginate($request->all());

        return ServiceFaqResource::collection($serviceFaqs);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  StoreRequest  $request
     * @return JsonResponse
     */
    public function store(StoreRequest $request): JsonResponse
    {
        $result = $this->service->create($request->validated());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language),
            ServiceFaqResource::make(data_get($result, 'data'))
        );
    }

    /**
     * Display the specified resource.
     *
     * @param  ServiceFaq $serviceFaq
     * @return JsonResponse
     */
    public function show(ServiceFaq $serviceFaq): JsonResponse
    {
        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            ServiceFaqResource::make($this->repository->show($serviceFaq))
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  ServiceFaq $serviceFaq
     * @param StoreRequest $request
     * @return JsonResponse
     */
    public function update(ServiceFaq $serviceFaq, StoreRequest $request): JsonResponse
    {
        $result = $this->service->update($serviceFaq, $request->validated());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED, locale: $this->language),
            ServiceFaqResource::make(data_get($result, 'data'))
        );
    }

    /**
     * @param int $id
     * @return JsonResponse
     */
    public function setActiveStatus(int $id): JsonResponse
    {
        $result = $this->service->setStatus($id);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            ServiceFaqResource::make(data_get($result, 'data'))
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  FilterParamsRequest $request
     * @return JsonResponse
     */
    public function destroy(FilterParamsRequest $request): JsonResponse
    {
        $this->service->delete($request->input('ids'));

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_DELETED, locale: $this->language),
            []
        );
    }

    /**
     * @return JsonResponse
     */
    public function dropAll(): JsonResponse
    {
        $this->service->dropAll();

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_DELETED, locale: $this->language),
            []
        );
    }
}
