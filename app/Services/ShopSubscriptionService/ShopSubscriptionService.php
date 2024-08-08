<?php
declare(strict_types=1);

namespace App\Services\ShopSubscriptionService;

use App\Helpers\ResponseError;
use App\Models\ShopSubscription;
use App\Models\Subscription;
use App\Services\CoreService;
use Throwable;

class ShopSubscriptionService extends CoreService
{

    protected function getModelClass(): string
    {
        return ShopSubscription::class;
    }

    /**
     * @param ShopSubscription $shopSubscription
     * @param array $data
     * @return array
     */
    public function update(ShopSubscription $shopSubscription, array $data): array
    {
        try {
            $subscription = Subscription::find(data_get($data, 'subscription_id'));

            if (empty($subscription)) {
                return ['status' => false, 'code' => ResponseError::ERROR_404];
            }

            $shopSubscription->update([
                'shop_id'         => data_get($data, 'shop_id'),
                'subscription_id' => $subscription->id,
                'expired_at'      => now()->addMonths($subscription->month),
                'price'           => $subscription->price,
                'type'            => data_get($subscription, 'type', 'order'),
                'active'          => data_get($data, 'active')
            ]);

            return [
                'status' => true,
                'code'   => ResponseError::NO_ERROR,
                'data'   => $shopSubscription
            ];

        } catch (Throwable $e) {
            $this->error($e);
            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_502,
                'message' => __('errors.' . ResponseError::ERROR_502, locale: $this->language)
            ];
        }
    }

    /**
     * @param array|null $ids
     * @return void
     */
    public function delete(?array $ids = []): void
    {
        $models = $this->model()->find(is_array($ids) ? $ids : []);

        foreach ($models as $model) {
            $model->delete();
        }
    }
}
