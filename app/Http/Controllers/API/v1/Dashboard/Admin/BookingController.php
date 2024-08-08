<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\Admin;

use App\Helpers\ResponseError;
use App\Http\Requests\Booking\AdminUpdateRequest;
use App\Http\Requests\Booking\ExtraTimeRequest;
use App\Http\Requests\Booking\NotesUpdateRequest;
use App\Http\Requests\Booking\StatusUpdateRequest;
use App\Http\Requests\Booking\TimesUpdateRequest;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\Booking\AdminStoreRequest;
use App\Http\Requests\Order\OrderTransactionRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Repositories\BookingRepository\BookingReportRepository;
use App\Repositories\BookingRepository\BookingRepository;
use App\Services\BookingService\BookingService;
use App\Traits\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Throwable;

class BookingController extends AdminBaseController
{
    use Notification;

    public function __construct(private BookingRepository $repository, private BookingService $service)
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
        $models = $this->repository->paginate($request->all());

        return BookingResource::collection($models);
    }

    /**
     * Display the specified resource.
     *
     * @param AdminStoreRequest $request
     * @return JsonResponse
     */
    public function store(AdminStoreRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->service->create($validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            BookingResource::collection(data_get($result, 'data'))
        );
    }

    /**
     * Display the specified resource.
     *
     * @param AdminStoreRequest $request
     * @return JsonResponse
     */
    public function calculate(AdminStoreRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->repository->calculate($validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(__('errors.' . ResponseError::NO_ERROR, locale: $this->language), $result);
    }

    /**
     * Display the specified resource.
     *
     * @param Booking $booking
     * @return JsonResponse
     */
    public function show(Booking $booking): JsonResponse
    {
        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            BookingResource::make($this->repository->show($booking))
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Booking $booking
     * @param AdminUpdateRequest $request
     * @return JsonResponse
     */
    public function update(Booking $booking, AdminUpdateRequest $request): JsonResponse
    {
        $result = $this->service->update($booking, $request->validated());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED, locale: $this->language),
            BookingResource::make(data_get($result, 'data'))
        );
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function bookingsByParent(int $id): JsonResponse
    {
        $bookings = $this->repository->bookingsByParentId($id);

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            BookingResource::collection($bookings)
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param int $id
     * @param StatusUpdateRequest $request
     * @return JsonResponse
     */
    public function statusUpdate(int $id, StatusUpdateRequest $request): JsonResponse
    {
        try {
            $model = $this->service->statusUpdate($id, $request->validated());

            $this->bookingStatusUpdateNotify($model);

            return $this->successResponse(
                __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED, locale: $this->language),
                BookingResource::make($model)
            );
        } catch (Throwable $e) {
            return $this->onErrorResponse([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * @param int $id
     * @param NotesUpdateRequest $request
     * @return JsonResponse
     */
    public function notesUpdate(int $id, NotesUpdateRequest $request): JsonResponse
    {
        try {
            $model = $this->service->notesUpdate($id, $request->validated());

            return $this->successResponse(
                __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED, locale: $this->language),
                BookingResource::make($model)
            );
        } catch (Throwable $e) {
            return $this->onErrorResponse([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * @param int $id
     * @param TimesUpdateRequest $request
     * @return JsonResponse
     */
    public function timesUpdate(int $id, TimesUpdateRequest $request): JsonResponse
    {
        try {
            $model = $this->service->timesUpdate($id, $request->validated());

            return $this->successResponse(
                __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED, locale: $this->language),
                BookingResource::make($model)
            );
        } catch (Throwable $e) {
            return $this->onErrorResponse([
                'message' => $e->getMessage() . $e->getFile() . $e->getLine()
            ]);
        }
    }

    /**
     * @param int $id
     * @param ExtraTimeRequest $request
     * @return JsonResponse
     */
    public function extraTime(int $id, ExtraTimeRequest $request): JsonResponse
    {
        try {
            $model = $this->service->extraTime($id, $request->validated());

            return $this->successResponse(
                __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED, locale: $this->language),
                BookingResource::make($model)
            );
        } catch (Throwable $e) {
            return $this->onErrorResponse([
                'message' => $e->getMessage() . $e->getFile() . $e->getLine()
            ]);
        }
    }

    /**
     * @param OrderTransactionRequest $request
     * @return JsonResponse
     */
    public function reportTransactions(OrderTransactionRequest $request): JsonResponse
    {
        try {
            $result = (new BookingReportRepository)->reportTransactions($request->validated());

            return $this->successResponse('Successfully', $result);
        } catch (Throwable $e) {
            return $this->onErrorResponse([
                'message' => $e->getMessage() . $e->getFile() . $e->getLine()
            ]);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function destroy(FilterParamsRequest $request): JsonResponse
    {
        $this->service->delete($request->input('ids', []), $request->all());

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
