<?php
declare(strict_types=1);

namespace App\Services\OrderService;

use App\Models\Currency;
use App\Models\Order;
use App\Models\Settings;
use App\Models\Shop;
use App\Services\CoreService;
use Exception;
use Throwable;

class POSOrderService extends CoreService
{
    protected function getModelClass(): string
    {
        return Order::class;
    }

    /**
     * @param array $data
     * @return array
     * @throws Throwable
     */
    public function create(array $data): array
    {
        $currency = Currency::currenciesList()->where('id', data_get($data, 'currency_id'))->first();

        if (empty($currency)) {
            $currency = Currency::currenciesList()->where('default', 1)->first();
        }

        if (!isset($data['user_id'])) {
            $data['user_id'] = auth('sanctum')->id();
        }

        $data['currency_id']    = $currency?->id;
        $data['rate']           = $currency?->rate;
        $data['total_price']    = 0;
        $data['commission_fee'] = 0;

        $parentId = null;
        $orders   = [];

        foreach ($data['data'] as $key => $item) {

            $shop = Shop::find($item['shop_id']);

            if (empty($shop)) {
                throw new Exception('shop not found');
            }

            $data['type']         = $shop->delivery_type;
            $data['shop_id']      = $item['shop_id'];
            $data['parent_id']    = $parentId;

            if ((int)Settings::where('key', 'order_auto_approved')->first()?->value === 1) {
                $data['status'] = Order::STATUS_ACCEPTED;
            }

            /** @var Order $order */
            $order = $this->model()->create($data);

            if (data_get($item, "images.$shop->id.0")) {

                $order->update([
                    'img' => $item['images'][$shop->id][0]
                ]);

                $order->uploads($item['images'][$shop->id]);

            }

            $order = (new OrderDetailService)->create($order, data_get($item, 'products', []));

            if ($key === 0) {
                $parentId = $order->id;
            }

            $orders[] = $order;
        }

        return $orders;
    }

}
