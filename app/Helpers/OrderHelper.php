<?php
declare(strict_types=1);

namespace App\Helpers;

use App\Models\Coupon;
use App\Models\DeliveryPoint;
use App\Models\DeliveryPrice;
use App\Models\Order;
use App\Models\Settings;
use App\Models\Shop;
use App\Models\Stock;
use App\Models\UserAddress;
use App\Models\WholeSalePrice;
use DB;
use Exception;
use Illuminate\Support\Collection;
use Throwable;

class OrderHelper
{
    /**
     * @throws Exception
     */
    public static function checkShopDelivery(
        ?Shop $shop = null,
        array $data = null,
        string $lang = 'en',
        array &$deliveryFee = []
    ): void
    {

        if (!isset($data['delivery_type']) || $data['delivery_type'] === Order::DIGITAL) {
            return;
        }

        if (empty($shop?->id)) {
            throw new Exception(__('errors.' . ResponseError::ERROR_435, locale: $lang));
        }

        if ($data['delivery_type'] === Order::DELIVERY && isset($data['delivery_price_id'])) {

            $deliveryPrice = self::deliveryPrice($shop, (int)$data['delivery_price_id'], $lang);

            $deliveryFee[] = $deliveryPrice;

        } else if ($data['delivery_type'] === Order::POINT && isset($data['delivery_point_id'])) {

            $deliveryPoint = DeliveryPoint::find($data['delivery_point_id'])?->price;

            $deliveryFee[] = [
                'shop_id' => $shop->id,
                'price'   => $deliveryPoint
            ];
        }

    }

    /**
     * @throws Exception
     */
    private static function deliveryPrice(Shop $shop, int $deliveryPriceId, string $lang): array
    {
        $deliveryPrice = DeliveryPrice::firstWhere('id', $deliveryPriceId);

        if (empty($deliveryPrice)) {
            throw new Exception(__('errors.' . ResponseError::ERROR_436, ['shop' => $shop->translation?->title], $lang));
        }

        if ($shop->delivery_type === Shop::DELIVERY_TYPE_SELLER) {

            $deliveryPrice = DeliveryPrice::when($deliveryPrice->region_id, fn($q, $id) => $q->where('region_id', $id))
                ->when($deliveryPrice->country_id, fn($q, $id) => $q->where('country_id', $id))
                ->when($deliveryPrice->city_id,    fn($q, $id) => $q->where('city_id',    $id))
                ->when($deliveryPrice->area_id,    fn($q, $id) => $q->where('area_id',    $id))
                ->where( 'shop_id', $shop->id)
                ->first();

            if (empty($deliveryPrice)) {
                throw new Exception(__('errors.' . ResponseError::ERROR_436, ['shop' => $shop->translation?->title], $lang));
            }

        }

        return [
            'shop_id' => $shop->id,
            'price'   => $deliveryPrice->price
        ];
    }

    /**
     * @param Stock $stock
     * @param int|null $quantity
     * @param bool $bonus
     * @return mixed
     */
    public static function actualQuantity(Stock $stock, mixed $quantity, bool $bonus = false): mixed
    {
        $product = $stock->product;

        if (empty($quantity)) {
            return 0;
        }

        if (!$bonus && $quantity < ($product?->min_qty ?? 0)) {

            $quantity = $product->min_qty;

        } elseif (!$bonus && $quantity > ($product?->max_qty ?? 0)) {

            $quantity = $product->max_qty;

        }

        return $quantity > $stock->quantity ? max($stock->quantity, 0) : $quantity;
    }

    public static function setItemParams(mixed $item, ?Stock $stock): array
    {
        $quantity = (int)$item['quantity'] ?? 0;

        $item = self::prepareByBonus($item, $stock, $quantity);

        return [
            'origin_price'      => data_get($item, 'origin_price', 0),
            'tax'               => data_get($item, 'tax', 0),
            'discount'          => data_get($item, 'discount', 0),
            'total_price'       => data_get($item, 'total_price', 0),
            'stock_id'          => $stock->id,
            'replace_stock_id'  => data_get($item, 'replace_stock_id'),
            'replace_quantity'  => data_get($item, 'replace_quantity'),
            'replace_note'      => data_get($item, 'replace_note'),
            'note'              => data_get($item, 'note'),
            'quantity'          => $quantity,
            'bonus'             => data_get($item, 'bonus', false),
        ];
    }

    private static function prepareByBonus(mixed $item, ?Stock $stock, int $quantity) {

        if (data_get($item, 'bonus')) {

            $item['origin_price'] = 0;
            $item['total_price']  = 0;
            $item['tax']          = 0;
            $item['discount']     = 0;

            return $item;
        }

        $price    = $stock?->price;
        $discount = $stock?->actual_discount * $quantity;
        $tax      = $stock?->tax_price * $quantity;

        $wholeSalePrice = $stock->wholeSalePrices
            ?->where('min_quantity', '<=', $quantity)
            ?->where('max_quantity', '>=', $quantity)
            ?->first();

        if (!empty($wholeSalePrice)) {
            /** @var WholeSalePrice $wholeSalePrice */
            $price    = $wholeSalePrice->price;
            $discount = 0;
            $tax      = 0;
        }

        $price *= $quantity;

        $item['origin_price'] = $price;
        $item['total_price']  = $price - $discount + $tax;
        $item['tax']          = $tax;
        $item['discount']     = $discount;

        return $item;
    }

    public static function updateStatCount(?Stock $stock, ?int $actualQuantity, $isIncrement = true): void
    {
        if (empty($stock)) {
            return;
        }

        $quantity = $stock->quantity - ($actualQuantity ?: $stock->quantity);
        $oCount   = max($stock->o_count + 1, 0);
        $odCount  = max($stock->od_count + 1, 0);

        if (!$isIncrement) {
            $quantity = $stock->quantity + ($actualQuantity ?: $stock->quantity);
            $oCount   = max($stock->o_count - 1, 0);
            $odCount  = max($stock->od_count - 1, 0);
        }

        $stock->update([
            'quantity' => $quantity,
            'o_count'  => $oCount,
            'od_count' => $odCount,
        ]);

        $stock->product->update([
            'o_count'  => $oCount,
            'od_count' => $odCount,
        ]);
    }

    public static function updateUserOrderStat(Order $order): void
    {
        $sum = Order::where('user_id', $order->user_id)
            ->select([
                'id',
                'user_id',
                'total_price'
            ])
            ->get();

        $order->user?->update([
            'o_count' => $sum?->count(),
            'o_sum'   => $sum?->sum('total_price'),
        ]);
    }

    /**`
     * @param $data
     * @param $shopId
     * @param $totalPrice
     * @param $rate
     * @param Collection $couponPrice
     * @param array $deliveryFee
     * @return Collection
     */
    public static function checkCoupon($data, $shopId, $totalPrice, $rate, Collection $couponPrice, array $deliveryFee): Collection
    {
        try {
            $name = data_get($data, "coupon.$shopId");
            $deliveryFee = collect($deliveryFee)->where('shop_id', $shopId)->first()?->price ?? 0;

            if (empty($name)) {
                return $couponPrice;
            }

            $coupon = Coupon::checkCoupon($name, $shopId)->first();

            if ($coupon?->for === 'delivery_fee') {

                $price = self::couponPrice($data, $coupon, $deliveryFee, $rate);

                if ($price === 0) {
                    return $couponPrice;
                }

                $couponPrice->push([
                    'shop_id' => $shopId,
                    'price'   => $price,
                    'coupon'  => $coupon,
                ]);

            } else if ($coupon?->for === 'total_price') {

                $price = self::couponPrice($data, $coupon, $totalPrice, $rate);

                if ($price === 0) {
                    return $couponPrice;
                }

                $couponPrice->push([
                    'shop_id' => $shopId,
                    'price'   => $price,
                    'coupon'  => $coupon,
                ]);
            }

            return $couponPrice;
        } catch (Throwable) {
            return $couponPrice;
        }
    }

    /**
     * @param array $data
     * @param Coupon|string $coupon
     * @param $totalPrice
     * @param mixed $rate
     * @param int|null $shopId
     * @return float|int|null
     */
    public static function couponPrice(array $data, Coupon|string $coupon, $totalPrice, mixed $rate, ?int $shopId = null): float|int|null
    {
        if (is_string($coupon)) {
            $coupon = Coupon::when($shopId,
                fn($q) => $q->checkCoupon($coupon, $shopId),
                fn($q) => $q->where('name', $coupon)
            )
                ->first();
        }

        if (empty($coupon)) {
            return 0;
        }

        $checkCoupon = DB::table($coupon->for === Coupon::bookingTotalPrice ? 'booking_coupons' : 'order_coupons')
            ->where([
                'user_id' => $data['user_id'] ?? auth('sanctum')->id(),
                'name'    => $coupon->name,
            ])
            ->exists();

        if ($checkCoupon || $coupon->qty <= 0) {
            return 0;
        }

        $couponPrice = $coupon->type === 'percent' ? ($totalPrice / 100) * $coupon->price : $coupon->price;

        return $couponPrice > 0 ? $couponPrice * $rate : 0;
    }

    /**
     * @param array $data
     * @param string|null $lang
     * @return void
     * @throws Exception
     */
    public static function checkPhoneIfRequired(array $data, ?string $lang = 'en'): void
    {
        $existPhone = DB::table('users')
            ->whereNotNull('phone')
            ->where('id', data_get($data, 'user_id'))
            ->exists();

        $phoneRequired = Settings::where('key', 'before_order_phone_required')->first();

        $myAddress = isset($data['address_id']) ? UserAddress::find($data['address_id']) : null;

        if (!empty($myAddress) && data_get($data, 'delivery_type') == Order::DELIVERY && $myAddress->phone) {
            return;
        }

        if (
            data_get($data, 'delivery_type') == Order::DELIVERY &&
            $phoneRequired?->value && (!$existPhone && !data_get($data, 'phone'))
        ) {
            throw new Exception(__('errors.' . ResponseError::ERROR_117, locale: $lang));
        }
    }
}
