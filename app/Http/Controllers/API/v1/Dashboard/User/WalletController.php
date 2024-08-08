<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Dashboard\User;

use App\Helpers\ResponseError;
use App\Http\Requests\BaseRequest;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\WalletHistory\SendRequest;
use App\Http\Resources\WalletHistoryResource;
use App\Models\Currency;
use App\Models\NotificationUser;
use App\Models\PointHistory;
use App\Models\PushNotification;
use App\Models\User;
use App\Models\WalletHistory;
use App\Repositories\WalletRepository\WalletHistoryRepository;
use App\Services\UserServices\UserWalletService;
use App\Services\WalletHistoryService\WalletHistoryService;
use App\Traits\Notification;
use DB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Throwable;

class WalletController extends UserBaseController
{
    use Notification;

    public function __construct(
        private WalletHistoryRepository $walletHistoryRepository,
        private WalletHistoryService $walletHistoryService
    )
    {
        parent::__construct();
    }

    /**
     * @param FilterParamsRequest $request
     *
     * @return AnonymousResourceCollection
     */
    public function walletHistories(FilterParamsRequest $request): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = auth('sanctum')->user();

        if (empty($user->wallet?->uuid)) {
            $user = (new UserWalletService)->create($user);
        }

        $data = $request->merge(['wallet_uuid' => $user->wallet->uuid])->all();

        $histories = $this->walletHistoryRepository->walletHistoryPaginate($data);

        return WalletHistoryResource::collection($histories);
    }

    /**
     * @param FilterParamsRequest $request
     * @return JsonResponse
     * @throws Throwable
     */
    public function store(FilterParamsRequest $request): JsonResponse
    {
        $result = $this->withDraw($request);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language),
            WalletHistoryResource::make(data_get($result, 'data'))
        );
    }

    /**
     * @param BaseRequest $request
     * @return array
     * @throws Throwable
     */
    public function withDraw(BaseRequest $request): array
    {
        $user = auth('sanctum')->user();

        if (empty($user->wallet) || $user->wallet->price < $request->input('price')) {
            return [
                'status' => false,
                'code'   => ResponseError::ERROR_109
            ];
        }

        $filter = $request->all();
        $filter['status'] = WalletHistory::PAID;
        $filter['type']   = 'withdraw';
        $filter['user']   = auth('sanctum')->user();

        return $this->walletHistoryService->create($filter);
    }

    /**
     * @param SendRequest $request
     * @return JsonResponse
     * @throws Throwable
     */
    public function send(SendRequest $request): JsonResponse
    {
        /** @var User $sendingUser */
        $sendingUser = User::with(['wallet', 'notifications'])->firstWhere('uuid', $request->input('uuid'));

        if (empty($sendingUser->wallet)) {
            return $this->onErrorResponse([
                'status' => false,
                'code'   => ResponseError::ERROR_109
            ]);
        }

        return DB::transaction(function () use ($request, $sendingUser) {

            $rate  = Currency::find($request->input('currency_id'))?->rate;
            $price = $request->input('price') / ($rate ?? 1);

            $request->merge([
                'price' => $price,
                'note'  => "$sendingUser->firstname $sendingUser->lastname"
            ]);

            $result = $this->withDraw($request);

            if (!data_get($result, 'status')) {
                return $this->onErrorResponse($result);
            }

            /** @var User $sender */
            $sender = auth('sanctum')->user();

            $filter = $request->all();
            $filter['status'] = WalletHistory::PAID;
            $filter['type']   = 'topup';
            $filter['user']   = $sendingUser;
            $filter['created_by'] = $sender->id;

            $result = $this->walletHistoryService->create($filter);

            if (!data_get($result, 'status')) {
                return $this->onErrorResponse($result);
            }

            $notification = $sendingUser
                ?->notifications
                ?->where('type', \App\Models\Notification::PUSH)
                ?->first();

            /** @var NotificationUser $notification */
            if ($notification?->notification?->active) {
                $this->sendNotification(
                    $sendingUser,
                    $sendingUser->firebase_token ?? [],
                    __('errors.' . ResponseError::WALLET_TOP_UP, ['sender' => "$sender->firstname $sender->lastname"], $sendingUser?->lang ?? $this->language),
                    __('errors.' . ResponseError::WALLET_TOP_UP, ['sender' => "$sender->firstname $sender->lastname"], $sendingUser?->lang ?? $this->language),
                    [
                        'id'     => $sendingUser->id,
                        'price'  => $price,
                        'type'   => PushNotification::WALLET_TOP_UP
                    ],
                    [$sendingUser->id]
                );
            }

            return $this->successResponse(
                __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language),
                WalletHistoryResource::make(data_get($result, 'data'))
            );
        });
    }

    /**
     * @param string $uuid
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function changeStatus(string $uuid, FilterParamsRequest $request): JsonResponse
    {
        if (
            !$request->input('status') ||
            !in_array($request->input('status'), [WalletHistory::REJECTED, WalletHistory::CANCELED])
        ) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_253]);
        }

        $result = $this->walletHistoryService->changeStatus($uuid, $request->input('status'));

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(__('errors.' . ResponseError::NO_ERROR, locale: $this->language));
    }

    /**
     * @param FilterParamsRequest $request
     * @return LengthAwarePaginator
     */
    public function pointHistories(FilterParamsRequest $request): LengthAwarePaginator
    {
        return PointHistory::where('user_id', auth('sanctum')->id())
            ->orderBy($request->input('column', 'created_at'), $request->input('sort', 'desc'))
            ->paginate($request->input('perPage', 10));
    }
}
