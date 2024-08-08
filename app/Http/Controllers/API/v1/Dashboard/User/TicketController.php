<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\User;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\Ticket\StoreRequest;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\Repositories\TicketRepository\TicketRepository;
use App\Services\TicketService\TicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TicketController extends UserBaseController
{

    public function __construct(private TicketRepository $ticketRepository, private TicketService $ticketService)
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
        $categories = $this->ticketRepository->paginate(
            $request->merge(['created_by' => auth('sanctum')->id()])->all()
        );

        return TicketResource::collection($categories);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreRequest $request
     * @return JsonResponse
     */
    public function store(StoreRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['created_by'] = auth('sanctum')->id();

        $result = $this->ticketService->create($validated);

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
        if ($ticket->created_by !== auth('sanctum')->id()) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            TicketResource::make($ticket)
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
        if ($ticket->created_by !== auth('sanctum')->id()) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        $validated = $request->validated();
        $validated['created_by'] = auth('sanctum')->id();

        $result = $this->ticketService->update($ticket, $validated);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED, locale: $this->language),
            TicketResource::make(data_get($result, 'data'))
        );
    }

}
