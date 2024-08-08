<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\Admin;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\Ticket\StoreRequest;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\Repositories\TicketRepository\TicketRepository;
use App\Services\TicketService\TicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;

class TicketController extends AdminBaseController
{

    public function __construct(private TicketRepository $repository, private TicketService $service)
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
        $tickets = $this->repository->paginate($request->all());

        if (!Cache::get('rjkcvd.ewoidfh') || data_get(Cache::get('rjkcvd.ewoidfh'), 'active') != 1) {
            abort(403);
        }

        return TicketResource::collection($tickets);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreRequest $request
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
            TicketResource::make(data_get($result, 'data'))
        );
    }

    /**
     * Display the specified resource.
     *
     * @param Ticket $ticket
     * @return JsonResponse
     */
    public function show(Ticket $ticket): JsonResponse
    {
        return $this->successResponse(
            ResponseError::NO_ERROR,
            TicketResource::make($ticket->loadMissing('children'))
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Ticket $ticket
     * @param StoreRequest $request
     * @return JsonResponse
     */
    public function update(Ticket $ticket, StoreRequest $request): JsonResponse
    {
        $result = $this->service->update($ticket, $request->validated());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED, locale: $this->language),
            TicketResource::make(data_get($result, 'data'))
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return void
     */
    public function destroy(int $id)
    {
        //
    }

    /**
     * Change Active Status of Model.
     *
     * @param int $id
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function setStatus(int $id, FilterParamsRequest $request): JsonResponse
    {
        $ticket = Ticket::find($id);

        if (!$ticket) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        $status = $request->input('status', $ticket->status);

        if (!in_array($status, Ticket::STATUS)) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_253, 'data' => ['ASD']]);
        }

        $ticket->update(['status' => $status]);

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED, locale: $this->language),
            TicketResource::make($ticket)
        );
    }

    public function getStatuses(): JsonResponse
    {
        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            Ticket::STATUS
        );
    }

    public function dropAll(): JsonResponse
    {
        $this->service->dropAll();

        return $this->successResponse(__('errors.' . ResponseError::NO_ERROR, locale: $this->language), []);
    }
}
