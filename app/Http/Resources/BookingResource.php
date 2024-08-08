<?php
declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var Booking|JsonResource $this */

        $priceByParent = $this->rate_total_price;
        $ids = "$this->id";
        $canceledAll = $this->status === Booking::STATUS_CANCELED;

        if ($this->relationLoaded('children')) {

            if ($this->children?->count() > 0) {
                $priceByParent += $this->children?->sum('rate_total_price');

                $ids .= '-' . $this->children?->implode('id', '-');

                $canceled = $this->children->pluck('status')->unique()->toArray();

                $canceledAll = in_array(Booking::STATUS_CANCELED, $canceled) && count($canceled) === 1;
            }

        }

        return [
            'id'                    => $this->when($this->id,                   $this->id),
            'total_price_by_parent' => $priceByParent,
            'ids_by_parent'         => $this->when($ids,                        $ids),
            'canceled_all'          => $canceledAll,
            'shop_id'               => $this->when($this->shop_id,              $this->shop_id),
            'seller_fee'            => $this->when($this->rate_seller_fee,      $this->rate_seller_fee),
            'service_master_id'     => $this->when($this->service_master_id,    $this->service_master_id),
            'master_id'             => $this->when($this->master_id,            $this->master_id),
            'user_id'               => $this->when($this->user_id,              $this->user_id),
            'parent_id'             => $this->when($this->parent_id,            $this->parent_id),
            'currency_id'           => $this->when($this->currency_id,          $this->currency_id),
            'gift_cart_id'          => $this->when($this->gift_cart_id,         $this->gift_cart_id),
            'rate'                  => $this->when($this->rate,                 $this->rate),
            'start_date'            => $this->when($this->start_date,           $this->start_date),
            'end_date'              => $this->when($this->end_date,             $this->end_date),
            'price'                 => $this->rate_price ?? 0,
            'discount'              => $this->rate_discount ?? 0,
            'commission_fee'        => $this->rate_commission_fee ?? 0,
            'extra_price'           => $this->rate_extra_price ?? 0,
            'service_fee'           => $this->rate_service_fee ?? 0,
            'total_price'           => $this->rate_total_price ?? 0,
            'coupon_price'          => $this->rate_coupon_price ?? 0,
            'gift_cart_price'       => $this->rate_gift_cart_price ?? 0,
            'tips'                  => $this->rate_tips ?? 0,
            'extra_time_price'      => $this->rate_extra_time_price ?? 0,
            'status'                => $this->when($this->status,               $this->status),
            'canceled_note'         => $this->when($this->canceled_note,        $this->canceled_note),
            'note'                  => $this->when($this->note,                 $this->note),
            'notes'                 => $this->when($this->notes,                $this->notes),
            'type'                  => $this->when($this->type,                 $this->type),
            'data'                  => $this->when($this->data,                 $this->data),
            'gender'                => $this->when($this->gender,               $this->gender),
            'created_at'            => $this->when($this->created_at,   $this->created_at?->format('Y-m-d H:i:s') . 'Z'),
            'updated_at'            => $this->when($this->updated_at,   $this->updated_at?->format('Y-m-d H:i:s') . 'Z'),

            // Relations
            'service_master'        => ServiceMasterResource::make($this->whenLoaded('serviceMaster')),
            'extras'                => BookingExtraResource::collection($this->whenLoaded('extras')),
            'master'                => UserResource::make($this->whenLoaded('master')),
            'user'                  => UserResource::make($this->whenLoaded('user')),
            'shop'                  => ShopResource::make($this->whenLoaded('shop')),
            'user_member_ship'      => UserMemberShipResource::make($this->whenLoaded('userMemberShip')),
            'currency'              => CurrencyResource::make($this->whenLoaded('currency')),
            'transaction'           => TransactionResource::make($this->whenLoaded('transaction')),
            'transactions'          => TransactionResource::collection($this->whenLoaded('transactions')),
            'review'                => ReviewResource::make($this->whenLoaded('review')),
            'reviews'               => ReviewResource::collection($this->whenLoaded('reviews')),
            'activities'            => $this->whenLoaded('activities'),
            'extra_times'           => BookingExtraTimeResource::collection($this->whenLoaded('extraTimes')),
        ];
    }
}
