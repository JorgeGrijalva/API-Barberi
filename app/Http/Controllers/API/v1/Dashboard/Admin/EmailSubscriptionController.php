<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\Admin;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Resources\EmailSubscriptionResource;
use App\Models\EmailSubscription;
use App\Services\EmailSettingService\EmailSettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EmailSubscriptionController extends AdminBaseController
{

    public function __construct(private EmailSettingService $service)
    {
        parent::__construct();
    }

    public function emailSubscriptions(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $emailSubscriptions = EmailSubscription::with([
                'user' => fn($q) => $q->select(['id', 'uuid', 'firstname', 'lastname', 'email'])
            ])
            ->when($request->input('user_id'), fn($q, $userId) => $q->where('user_id', $userId))
            ->orderBy($request->input('column', 'id'), $request->input('sort', 'desc'))
            ->paginate($request->input('perPage', 10));

        return EmailSubscriptionResource::collection($emailSubscriptions);
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function setActive(int $id): JsonResponse
    {
        $this->service->setActive($id);

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
