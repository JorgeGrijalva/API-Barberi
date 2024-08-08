<?php
declare(strict_types=1);

namespace App\Services\OrderService;

use App\Helpers\OrderHelper;
use App\Helpers\ResponseError;
use App\Models\Bonus;
use App\Models\NotificationUser;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\PushNotification;
use App\Models\Stock;
use App\Models\WalletHistory;
use App\Services\CoreService;
use App\Services\TransactionService\TransactionService;
use App\Services\WalletHistoryService\WalletHistoryService;
use App\Traits\Notification;
use Exception;
use Throwable;

class OrderDetailService extends CoreService
{
    use Notification;

    /**
     * @return string
     */
    protected function getModelClass(): string
    {
        return OrderDetail::class;
    }

    /**
     * @throws Exception
     * @throws Throwable
     */
    public function create(Order $order, array $data): Order
    {
        foreach ($order->orderDetails as $orderDetail) {

            OrderHelper::updateStatCount($orderDetail->stock, $orderDetail?->quantity, false);

            $orderDetail?->delete();

        }

        return $this->update($order, $data);
    }

    /**
     * @throws Throwable
     */
    public function update(Order $order, array $data): Order
    {
        $count = 0;
        $digitalCount = 0;
        $replaceDifferent = [];

        foreach ($data as $item) {

            /** @var Stock $stock */
            $stock = Stock::with([
                'product:id,status,active,shop_id,min_qty,max_qty,tax,interval,digital',
                'product.shop:id,status,latitude,longitude',
                'discount' => fn($q) => $q
                    ->where('start', '<=', today())
                    ->where('end', '>=', today())
                    ->where('active', 1),
                'wholeSalePrices' => fn($q) => $q
                    ->where('min_quantity', '<=', $item['quantity'])
                    ->where('max_quantity', '>=', $item['quantity']),
            ])
                ->find($item['stock_id']);

            if (!$stock?->product?->active || $stock?->product?->status != Product::PUBLISHED) {
                continue;
            }

            $actualQuantity = OrderHelper::actualQuantity($stock, ($item['quantity'] ?? 0), $item['bonus'] ?? false);

            if (empty($actualQuantity) || $actualQuantity <= 0) {
                continue;
            }

            $count += 1;
            $digitalCount += $stock->product->digital ? 1 : 0;

            $item['quantity'] = $actualQuantity;

            $replaceStock = null;

            if (isset($item['replace_stock_id'])) {

                $replaceStock = Stock::find($item['replace_stock_id']);

                $this->replaceCalculate($order, $stock, $item, $replaceStock, $replaceDifferent);

            }

            /** @var OrderDetail $orderDetail */
            $orderDetail = $order->orderDetails()->updateOrCreate(
                [
                    'stock_id' => !empty($replaceStock) ? $replaceStock->id : $stock->id,
                    'bonus'    => $item['bonus'] ?? false,
                ],
                OrderHelper::setItemParams($item, $stock)
            );

            if (data_get($item, 'images.0')) {
                $orderDetail->uploads(data_get($item, 'images'));
            }

            OrderHelper::updateStatCount($stock, $actualQuantity);

            if (!empty($replaceStock)) {
                OrderHelper::updateStatCount($replaceStock, (int)($item['replace_quantity'] ?? 0), false);
            }

        }

        $this->replaceUpdate($order, $replaceDifferent);

        if ($count === 0) {
            throw new Exception(__('errors.' . ResponseError::CANT_UPDATE_EMPTY_ORDER, locale: $this->language));
        }

        if ($count === $digitalCount) {
            $order->update([
                'status' => Order::STATUS_DELIVERED,
            ]);
        }

        return $order;
    }

    /**
     * @param Order $order
     * @param Stock $stock
     * @param Stock|null $replaceStock
     * @param array $item
     * @param array $replaceDifferent
     * @return array
     */
    private function replaceCalculate(
        Order $order,
        Stock $stock,
        array $item,
        ?Stock $replaceStock = null,
        array &$replaceDifferent = []
    ): array
    {
        if (empty($replaceStock)) {
            return $replaceDifferent;
        }

        $replaceBonusStock = Bonus::where([
            ['stock_id', $replaceStock->id],
            ['expired_at', '>', now()],
            ['type', Bonus::TYPE_COUNT],
        ])
            ->first();

        if (!empty($replaceBonusStock?->id)) {
            $order->orderDetails()
                ->where(['bonus' => true, 'stock_id' => $replaceBonusStock->bonus_stock_id])
                ->delete();
        }

        $replaceDifferent[$replaceStock->id] = [
            'type'  => 'topup',
            'price' => $stock->total_price - $replaceStock->total_price,
        ];

        if ($replaceStock->total_price > $stock->total_price) {
            $replaceDifferent[$replaceStock->id] = [
                'type'  => 'withdraw',
                'price' => $replaceStock->total_price - $stock->total_price,
            ];
        }

        $bonusStock = Bonus::where([
            ['stock_id', $stock->id],
            ['expired_at', '>', now()],
            ['type', Bonus::TYPE_COUNT],
        ])
            ->first();

        if (!$bonusStock?->id || !$bonusStock?->stock?->id) {
            return $replaceDifferent;
        }

        $bonusItem = [
            'quantity' => $bonusStock->bonus_quantity * (int)floor($item['quantity'] / $bonusStock->value)
        ];

        /** @var OrderDetail $orderDetail */
        $order->orderDetails()->updateOrCreate(
            [
                'stock_id' => $bonusStock->bonus_stock_id,
            ],
            OrderHelper::setItemParams($bonusItem, $bonusStock)
        );

        return $replaceDifferent;
    }

    /**
     * @param Order $order
     * @param array $replaceDifferent
     * @return void
     * @throws Throwable
     */
    private function replaceUpdate(Order $order, array $replaceDifferent): void
    {
//        if ($order->transaction?->status !== Transaction::STATUS_PAID) {
//            return;
//        }

        (new TransactionService)->digitalFile($order);

        if (count($replaceDifferent) <= 0) {
            return;
        }

        $totalPrice = 0;

        foreach ($replaceDifferent as $item) {
            $totalPrice = $item['type'] === 'topup' ? $totalPrice + $item['price'] : $totalPrice - $item['price'];
        }

        (new WalletHistoryService)->create([
            'type'   => $totalPrice < 0 ? 'topup' : 'withdraw',
            'price'  => (double)str_replace('-', '', (string)$totalPrice),
            'note'   => __('errors.' . ResponseError::REPLACE_PRODUCT, ['id' => $order->id], $order->user?->lang ?? $this->language),
            'status' => WalletHistory::PAID,
            'user'   => $order->user,
        ]);

        $notification = $order->user
            ?->notifications
            ?->where('type', \App\Models\Notification::PUSH)
            ?->first();

        /** @var NotificationUser $notification */
        if (!$notification?->notification?->active) {
            return;
        }

        $key  = $totalPrice > 0 ? ResponseError::WALLET_TOP_UP : ResponseError::WALLET_WITHDRAW;
        $type = $totalPrice > 0 ? PushNotification::WALLET_TOP_UP : PushNotification::WALLET_WITHDRAW;

        $this->sendNotification(
            $order->user,
            $order->user->firebase_token ?? [],
            __("errors.$key", ['sender' => 'system'], $order->user?->lang ?? $this->language),
            __("errors.$key", ['sender' => 'system'], $order->user?->lang ?? $this->language),
            [
                'id'     => $order->user->id,
                'price'  => $totalPrice,
                'type'   => $type
            ],
            [$order->user_id]
        );
    }

}
