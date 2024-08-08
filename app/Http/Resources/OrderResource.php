<?php

namespace App\Http\Resources;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var Order|JsonResource $this */

        $priceByParent = $this->rate_total_price;
        $ids = "$this->id";

        if ($this->relationLoaded('children')) {

            if ($this->children?->count() > 0) {
                $priceByParent += ($this->children?->sum('total_price') * ($this->rate <= 0 ? 1 : $this->rate));

                $ids .= '-' . $this->children?->implode('id', '-');
            }

        }

        return [
            'id'                            => $this->when($this->id, $this->id),
            'user_id'                       => $this->when($this->user_id, $this->user_id),
            'total_price'                   => $this->when($this->rate_total_price, $this->rate_total_price),
            'total_price_by_parent'         => $this->when($priceByParent, $priceByParent),
            'ids_by_parent'                 => $this->when($ids, $ids),
            'origin_price'                  => $this->when($this->origin_price, $this->origin_price),
            'seller_fee'                    => $this->when($this->seller_fee, $this->seller_fee),
            'rate'                          => $this->when($this->rate, $this->rate),
            'note'                          => $this->when(isset($this->note), (string) $this->note),
            'order_details_count'           => $this->when($this->order_details_count, (int) $this->order_details_count),
            'order_details_sum_quantity'    => $this->when($this->order_details_sum_quantity, $this->order_details_sum_quantity),
            'tax'                           => $this->when($this->rate_total_tax, $this->rate_total_tax),
            'commission_fee'                => $this->when($this->rate_commission_fee, $this->rate_commission_fee),
            'service_fee'                   => $this->when($this->rate_service_fee, $this->rate_service_fee),
            'status'                        => $this->when($this->status, $this->status),
            'location'                      => $this->when($this->location, $this->location),
            'address'                       => $this->when($this->address, $this->address),
            'delivery_type'                 => $this->when($this->delivery_type, $this->delivery_type),
            'delivery_fee'                  => $this->when($this->rate_delivery_fee, $this->rate_delivery_fee),
            'delivery_date'                 => $this->when($this->delivery_date, $this->delivery_date),
            'phone'                         => $this->when($this->phone, $this->phone),
            'username'                      => $this->when($this->username, $this->username),
            'current'                       => (bool)$this->current,
            'img'                           => $this->when($this->img, $this->img),
            'total_discount'                => $this->when($this->rate_total_discount, $this->rate_total_discount),
            'coupon_price'                  => $this->when($this->rate_coupon_price, $this->rate_coupon_price),
            'type'                          => $this->when($this->type, $this->type),
            'track_name'                    => $this->when($this->track_name, $this->track_name),
            'track_id'                      => $this->when($this->track_id, $this->track_id),
            'track_url'                     => $this->when($this->track_url, $this->track_url),
            'cart_id'                       => $this->when($this->cart_id, $this->cart_id),
            'parent_id'                     => $this->when($this->parent_id, $this->parent_id),
            'created_at'                    => $this->when($this->created_at, $this->created_at?->format('Y-m-d H:i:s') . 'Z'),
            'updated_at'                    => $this->when($this->updated_at, $this->updated_at?->format('Y-m-d H:i:s') . 'Z'),

            'deliveryman'                   => UserResource::make($this->whenLoaded('deliveryman')),
            'shop'                          => ShopResource::make($this->whenLoaded('shop')),
            'currency'                      => CurrencyResource::make($this->whenLoaded('currency')),
            'user'                          => UserResource::make($this->whenLoaded('user')),
            'details'                       => OrderDetailResource::collection($this->whenLoaded('orderDetails')),
            'transaction'                   => TransactionResource::make($this->whenLoaded('transaction')),
            'transactions'                  => TransactionResource::collection($this->whenLoaded('transactions')),
            'review'                        => ReviewResource::make($this->whenLoaded('review')),
            'reviews'                       => ReviewResource::collection($this->whenLoaded('reviews')),
            'point_histories'               => PointResource::collection($this->whenLoaded('pointHistories')),
            'order_refunds'                 => OrderRefundResource::collection($this->whenLoaded('orderRefunds')),
            'coupon'                        => CouponResource::make($this->whenLoaded('coupon')),
            'galleries'                     => GalleryResource::collection($this->whenLoaded('galleries')),
            'logs'                          => ModelLogResource::collection($this->whenLoaded('logs')),
            'my_address'                    => UserAddressResource::make($this->whenLoaded('myAddress')),
            'payment_to_partner'			=> PaymentToPartnerResource::make($this->whenLoaded('paymentToPartner')),
            'delivery_point'                => DeliveryPointResource::make($this->whenLoaded('deliveryPoint')),
            'delivery_price'                => DeliveryPriceResource::make($this->whenLoaded('deliveryPrice')),
            'notes'                         => OrderStatusNoteResource::collection($this->whenLoaded('notes')),
        ];
    }
}
