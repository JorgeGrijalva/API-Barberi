<?php
declare(strict_types=1);

namespace App\Services\InviteService;

use App\Helpers\ResponseError;
use App\Models\Invitation;
use App\Models\PushNotification;
use App\Models\Shop;
use App\Models\User;
use App\Services\CoreService;
use App\Traits\Notification;
use DB;
use Exception;
use Throwable;

final class InviteService extends CoreService
{
    use Notification;

    protected function getModelClass(): string
    {
        return Invitation::class;
    }

    public function create(string $uuid, array $data): array
    {
        try {
            /** @var Shop $shop */
            $shop = Shop::with(['seller:id,firebase_token,lang'])
                ->select(['id', 'user_id'])
                ->firstWhere('uuid', $uuid);

            /** @var User $user */
            $user = auth('sanctum')->user();

            if ($user->hasAnyRole(['seller', 'admin'])) {
                throw new Exception(__('errors.' . ResponseError::ERROR_257, locale: $user->lang ?? $this->language));
            }

            $invite = $this->model()
                ->updateOrCreate([
                    'user_id'    => auth('sanctum')->id(),
                    'created_by' => auth('sanctum')->id()
                ], [
                    'shop_id' => $shop->id,
                    'role'    => $data['role'] ?? 'master',
                ]);

            $sellerToken = $shop->seller?->firebase_token;

            $this->sendNotification(
                $invite,
                is_array($sellerToken) ? $sellerToken : [$sellerToken],
                __('errors.' . ResponseError::INVITE_FOR_SHOP, $shop->seller?->lang ?? $this->language),
                $invite->id,
                [
                    'id'   => $invite->id,
                    'type' => PushNotification::INVITE_MASTER
                ],
                [$shop->user_id]
            );

            return [
                'status' => true,
                'code'   => ResponseError::NO_ERROR,
                'data'   => $invite
            ];
        } catch (Exception $e) {
            $this->error($e);
            return ['status' => false, 'code' => ResponseError::ERROR_501, 'message' => $e->getMessage()];
        }
    }

    public function sellerCreate(array $data): array
    {
        try {
            /** @var User $user */
            $user = User::with(['roles'])->firstWhere('id', data_get($data, 'user_id'));

            if ($user->hasAnyRole(['seller', 'admin'])) {
                throw new Exception(__('errors.' . ResponseError::ERROR_257, locale: $user->lang ?? $this->language));
            }

            $invite = $this->model()
                ->updateOrCreate([
                    'user_id'    => $user->id,
                    'created_by' => auth('sanctum')->id()
                ], $data);

            $this->sendNotification(
                $invite,
                is_array($user->firebase_token) ? $user->firebase_token : [$user->firebase_token],
                __('errors.' . ResponseError::INVITE_MASTER, ['shop' => data_get($data, 'shop_name', 'null')], $user->lang ?? $this->language),
                $invite->id,
                [
                    'id'   => $invite->id,
                    'type' => PushNotification::INVITE_MASTER
                ],
                [$user->id]
            );

            return [
                'status' => true,
                'code'   => ResponseError::NO_ERROR,
                'data'   => $invite
            ];
        } catch (Exception $e) {
            $this->error($e);
            return ['status' => false, 'code' => ResponseError::ERROR_501, 'message' => $e->getMessage()];
        }
    }

    public function changeStatus(int $id, array $data): array
    {
        try {
            $invite = $this->model()
                ->with([
                    'user:id,firebase_token,lang,firstname,lastname,img',
                    'user.serviceMasters',
                    'user.roles',
                    'createdBy:id,firebase_token,lang,firstname,lastname,img'
                ])
                ->whereHas('user')
                ->firstWhere(['id' => $id, 'shop_id' => data_get($data, 'shop_id')]);


            /** @var User $authUser */
            $authUser = auth('sanctum')->user();

            if (!$invite) {
                return [
                    'status'  => false,
                    'code'    => ResponseError::ERROR_404,
                    'message' => __('errors.' . ResponseError::ERROR_404, locale: $authUser->lang ?? $this->language)
                ];
            }

            /** @var Invitation $invite */
            if (
                !$authUser->hasRole('admin')
                && $invite->created_by === $authUser->id
                && !in_array($data['status'], [Invitation::STATUS[Invitation::CANCELED], Invitation::STATUS[Invitation::REJECTED]])
            ) {
                return [
                    'status'  => false,
                    'code'    => ResponseError::ERROR_400,
                    'message' => __('errors.' . ResponseError::ERROR_256, locale: $authUser->lang ?? $this->language)
                ];
            }

            $data['status'] = Invitation::STATUS[$data['status']];

            if ($invite->status === Invitation::ACCEPTED && in_array($data['status'], [Invitation::REJECTED, Invitation::CANCELED])) {
                $invite->user?->serviceMasters()?->update(['active' => false]);
            }

            /** @var Invitation $invite */
            $invite->update($data);

            if ($data['status'] === Invitation::ACCEPTED) {
                $roles   = $invite->user->roles?->pluck('name')?->toArray() ?? [];
                $roles[] = $invite->role;

                $invite->user->syncRoles($roles);
            }

            $owner = $invite->createdBy;

            $status = Invitation::STATUS_BY[$invite->status] ?? $invite->status;

            $this->sendNotification(
                $invite,
                is_array($owner?->firebase_token) ? $owner?->firebase_token : [$owner?->firebase_token],
                __('errors.' . ResponseError::INVITE_STATUS_CHANGED, ['status' => $status], $owner->lang ?? $this->language),
                $invite->id,
                [
                    'id'   => $invite->id,
                    'type' => PushNotification::INVITE_MASTER
                ],
                [$invite->created_by] 
            );

            $user = $invite->user;

            $this->sendNotification(
                $invite,
                is_array($user?->firebase_token) ? $user?->firebase_token : [$user?->firebase_token],
                __('errors.' . ResponseError::INVITE_STATUS_CHANGED, ['status' => $status], $user->lang ?? $this->language),
                $invite->id,
                [
                    'id'   => $invite->id,
                    'type' => PushNotification::INVITE_MASTER
                ],
                [$invite->created_by]
            );

            return [
                'status' => true,
                'data'   => $invite,
            ];
        } catch (Throwable $e) {
            $this->error($e);
            return ['status' => false, 'code' => ResponseError::ERROR_502, 'message' => $e->getMessage()];
        }
    }

    public function delete(array $ids, ?int $shopId = null, ?int $userId = null)
    {
        DB::table('invitations')->whereIn('id', $ids)
            ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
            ->when($userId, fn($q) => $q->where('created_by', $userId))
            ->delete();
    }
}
