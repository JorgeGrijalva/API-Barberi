<?php
declare(strict_types=1);

namespace App\Services\ShopServices;

use App\Helpers\ResponseError;
use App\Models\PushNotification;
use App\Models\Shop;
use App\Services\CoreService;
use App\Traits\Notification;
use Exception;

class ShopActivityService extends CoreService
{
    use Notification;

    /**
     * @return string
     */
    protected function getModelClass(): string
    {
        return Shop::class;
    }

    public function changeStatus(string $uuid,  $status): array
    {
        /** @var Shop $shop */
        $shop = $this->model()
            ->with(['seller'])
            ->whereHas('seller')
            ->firstWhere('uuid', $uuid);

        if (!$shop) {
            return [
                'status'  => false,
                'message' => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
            ];
        }

        if ($shop->seller->hasRole('admin')) {
            return [
                'status'  => false,
                'message' => __('errors.' . ResponseError::ERROR_207, locale: $this->language)
            ];
        }

        $shop->update(['status' => $status]);

        if ($status == 'approved') {
            $shop->seller->syncRoles('seller');
        }

        $messageKey = ResponseError::SHOP_STATUS_CHANGED;

        $this->sendNotification(
            $shop,
            $shop->seller->firebase_token ?? [],
            __("errors.$messageKey", ['status' => $shop->status], $shop->seller?->lang ?? $this->language),
            __("errors.$messageKey", ['status' => $shop->status], $shop->seller?->lang ?? $this->language),
            [
                'id' => $shop->user_id,
                'type' => PushNotification::STATUS_CHANGED
            ],
            [$shop->user_id]
        );

        return ['status' => true, 'message' => ResponseError::NO_ERROR, 'data' => $shop];
    }

    /**
     * @throws Exception
     */
    public function changeOpenStatus(string $uuid)
    {
        $shop = $this->model()->firstWhere('uuid', $uuid);

        if (empty($shop)) {
            throw new Exception( __('errors.' . ResponseError::ERROR_404, locale: $this->language));
        }

        /** @var Shop $shop */
        $shop->update(['open' => !$shop->open]);
    }

}
