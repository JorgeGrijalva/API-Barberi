<?php
declare(strict_types=1);

namespace App\Services\GiftCartService;

use App\Helpers\ResponseError;
use App\Models\GiftCart;
use App\Models\PushNotification;
use App\Models\User;
use App\Models\UserGiftCart;
use App\Services\CoreService;
use App\Traits\Notification;
use App\Traits\SetTranslations;
use Exception;
use Illuminate\Database\Eloquent\Model;

class GiftCartService extends CoreService
{
    use SetTranslations, Notification;

    /**
     * @return string
     */
    protected function getModelClass(): string
    {
        return GiftCart::class;
    }

    /**
     * @param array $data
     * @return array
     */
    public function create(array $data): array
    {
        try {
            $gifCart = $this->model()->create($data);

            $this->setTranslations($gifCart, $data);

            return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $gifCart];
        } catch (Exception $e) {
            return ['status' => false, 'code' => ResponseError::ERROR_400, 'message' => $e->getMessage()];
        }
    }

    /**
     * @param GiftCart $giftCart
     * @param array $data
     * @return array
     */
    public function update(GiftCart $giftCart, array $data): array
    {
        try {
            $giftCart->update($data);

            $this->setTranslations($giftCart, $data);

            return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $giftCart];
        }
        catch (Exception $e) {
            return ['status' => false, 'code' => ResponseError::ERROR_400, 'message' => $e->getMessage()];
        }
    }

    /**
     * @param GiftCart $giftCart
     * @param $userId
     * @return Model|UserGiftCart
     */
    public function attach(GiftCart $giftCart, $userId): Model|UserGiftCart
    {
        return UserGiftCart::create([
            'gift_cart_id' => $giftCart->id,
            'user_id'      => $userId,
            'expired_at'   => date('Y-m-d H:i:s', strtotime("+$giftCart->time")),
            'price'        => $giftCart->price,
            'active'       => 0
        ]);
    }

    /**
     * @param array $ids
     * @param int|null $shopId
     * @return void
     */
    public function delete(array $ids, ?int $shopId = null): void
    {
        GiftCart::whereIn('id', $ids)
            ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
            ->delete();
    }

    /**
     * @param array $data
     * @return array
     */
    public function send(array $data): array
    {
        try {
            /** @var User $sender */
            $sender = auth('sanctum')->user();

            $userGiftCart = UserGiftCart::find($data['user_gift_cart_id']);

            if (empty($userGiftCart) || $userGiftCart->user_id !== $sender->id) {
                return ['status' => false, 'code' => ResponseError::ERROR_404];
            }

            if ($userGiftCart->expired_at < now()) {
                return ['status' => false, 'code' => ResponseError::ERROR_511];
            }

            $userGiftCart->update(['user_id' => $data['user_id']]);

            $receiver = User::find($data['user_id']);

            $this->sendNotification(
                $receiver,
                $receiver->firebase_token ?? [],
                __('errors.' . ResponseError::SEND_GIFT_CART, ['sender' => "$sender->firstname $sender->lastname"], $receiver?->lang ?? $this->language),
                __('errors.' . ResponseError::SEND_GIFT_CART, ['sender' => "$sender->firstname $sender->lastname"], $receiver?->lang ?? $this->language),
                [
                    'id' => $receiver->id,
                    'type' => PushNotification::NOTIFICATIONS
                ],
                [$receiver->id]

            );

            return ['status' => true, 'code' => ResponseError::NO_ERROR];

        } catch (Exception $e) {
            return ['status' => false, 'code' => ResponseError::ERROR_400, 'message' => $e->getMessage()];
        }
    }

}
