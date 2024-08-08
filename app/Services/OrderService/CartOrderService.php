<?php
declare(strict_types=1);

namespace App\Services\OrderService;

use App\Helpers\OrderHelper;
use App\Helpers\ResponseError;
use App\Models\Cart;
use App\Models\Currency;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\Settings;
use App\Models\Transaction;
use App\Services\CoreService;
use App\Services\TransactionService\TransactionService;
use Exception;
use Log;

class CartOrderService extends CoreService
{
    protected function getModelClass(): string
    {
        return Order::class;
    }

    /**
     * @param array $data
     * @param array $notes
     * @return array
     * @throws Exception
     */
    public function create(array $data, array $notes = []): array
    {
        /** @var Cart $cart */
        $cart = Cart::with([
            'paymentProcess',
            'userCarts.cartDetails.shop' => fn($q) => $q->where('status', 'approved')
                ->select('id', 'status', 'latitude', 'longitude', 'delivery_type'),
            'userCarts.cartDetails.cartDetailProducts.stock.product:id,status,active,shop_id,min_qty,max_qty,tax,digital',
            'userCarts.cartDetails.cartDetailProducts.stock.discount' => fn($q) => $q
                ->where('start', '<=', today())
                ->where('end', '>=', today())
                ->where('active', 1),
        ])->find($data['cart_id']);

        if (empty($cart?->userCarts)) {
            throw new Exception(__('errors.' . ResponseError::ERROR_404, locale: $this->language));
        }

        $currency = Currency::currenciesList()->where('id', data_get($data, 'currency_id'))->first();

        if (empty($currency)) {
            $currency = Currency::currenciesList()->where('default', 1)->first();
        }

        $count        = 0;
        $digitalCount = 0;
        $orders       = [];

        foreach ($cart->userCarts as $userCart) {

            $cartDetails = $userCart->cartDetails;

            if ($cartDetails?->count() == 0) {
                $userCart->delete();
                continue;
            }

            foreach ($cartDetails as $cartDetail) {

                if (empty($cartDetail->shop)) {
                    $cartDetail->delete();
                    continue;
                }

                $data['currency_id']    = $currency?->id;
                $data['rate']           = $currency?->rate;
                $data['type']           = $cartDetail->shop->delivery_type;
                $data['total_price']    = 0;
                $data['commission_fee'] = 0;
                $data['shop_id']        = $cartDetail->shop_id; // не удалять. Может быть опасно при оплате через карты или платёжные системы
                $data['note']           = data_get($notes, "order.$cartDetail->shop_id");

                if ((int)Settings::where('key', 'order_auto_approved')->first()?->value === 1) {
                    $data['status'] = Order::STATUS_ACCEPTED;
                }

                $order = Order::updateOrCreate([
                    'cart_id' => $cart->id,
                    'shop_id' => $cartDetail->shop_id,
                    'user_id' => $cart->owner_id,
                ], $data);

                if (data_get($data, "images.{$cartDetail->shop->id}.0")) {
                    $order->update(['img' => data_get($data, "images.{$cartDetail->shop->id}.0")]);
                    $order->uploads(data_get($data, "images.{$cartDetail->shop->id}"));
                }

                foreach ($cartDetail->cartDetailProducts as $cartDetailProduct) {

                    $stock = $cartDetailProduct->stock;

                    if (!$stock || !$stock->product?->active || $stock->product?->status !== Product::PUBLISHED) {
                        $cartDetailProduct->galleries()->delete();
                        $cartDetailProduct->delete();
                        continue;
                    }

                    $actualQuantity = OrderHelper::actualQuantity($stock, $cartDetailProduct->quantity, $cartDetailProduct->bonus);

                    if (empty($actualQuantity) || $actualQuantity <= 0) {
                        $cartDetail->delete();
                        continue;
                    }

                    $count += 1;
                    $digitalCount += $stock->product->digital ? 1 : 0;

                    $cartDetailProduct->setAttribute('note', data_get($notes, "product.$cartDetail->shop_id", ''));
                    $cartDetailProduct->setAttribute('quantity', $actualQuantity);

                    /** @var OrderDetail $orderDetail */
                    $orderDetail = $order->orderDetails()->updateOrCreate(
                        [
                            'stock_id' => $stock->id,
                            'bonus'    => $cartDetailProduct->bonus,
                        ],
                        OrderHelper::setItemParams($cartDetailProduct, $stock)
                    );

                    $cartDetailProduct->galleries()->update([
                        'loadable_type' => OrderDetail::class,
                        'loadable_id'   => $orderDetail->id,
                    ]);

                    OrderHelper::updateStatCount($stock, $actualQuantity);

                }

                $orders[$order->id] = $order;

            }

        }

        $status = data_get($cart?->paymentProcess, 'data.trx_status');

        if ($status) {
            $this->createTransactionByOrder($orders, $cart, $status, $count, $digitalCount);
        }

        $parentId = array_key_first($orders);

        foreach ($orders as $key => $order) {

            if ($key === $parentId) {
                continue;
            }

            $order->update([
                'parent_id' => $parentId
            ]);

        }

        $cart->delete();

        return $orders;
    }

    /**
     * @param array $orders
     * @param Cart $cart
     * @param string $status
     * @param int $count
     * @param int $digitalCount
     * @return void
     */
    private function createTransactionByOrder(array $orders, Cart $cart, string $status, int $count, int $digitalCount): void
    {

        Log::error('asdas', [
            count($orders),
            $status
        ]);

        foreach ($orders as $order) {

            /** @var Order $order */
            $order->createTransaction([
                'price'              => $order->total_price ?? $order?->transaction?->price,
                'user_id'            => $order->user_id ?? auth('sanctum')->id(),
                'payment_sys_id'     => data_get($cart?->paymentProcess?->data, 'payment_id'),
                'payment_trx_id'     => $cart?->paymentProcess?->id,
                'note'               => "Transaction for order #$order->id",
                'perform_time'       => now(),
                'status'             => $status,
                'status_description' => "Transaction for order #$order->id"
            ]);

            if ($status === Transaction::STATUS_PAID || $order->transaction?->status === Transaction::STATUS_PAID) {
                (new TransactionService)->digitalFile($order);
            }

            if ($count === $digitalCount) {
                $order->update(['status' => Order::STATUS_DELIVERED]);
            }

            $order->fresh(['transaction']);

        }

    }

}
