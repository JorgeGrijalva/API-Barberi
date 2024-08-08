<?php
declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ServiceMaster;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceMasterResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var ServiceMaster|JsonResource $this */

        return [
            'id'              => $this->when($this->id,                   $this->id),
            'discount'        => $this->when($this->discount,             $this->rate_discount),
            'service_id'      => $this->when($this->service_id,           $this->service_id),
            'master_id'       => $this->when($this->master_id,            $this->master_id),
            'shop_id'         => $this->when($this->shop_id,              $this->shop_id),
            'commission_fee'  => $this->when($this->rate_commission_fee,  $this->rate_commission_fee),
            'price'           => $this->when($this->rate_price,           $this->rate_price),
            'total_price'     => $this->when($this->rate_total_price,     $this->rate_total_price),
            'active'          => $this->when($this->active,               $this->active),
            'interval'        => $this->when($this->interval,             $this->interval),
            'pause'           => $this->when($this->pause,                $this->pause),
            'type'            => $this->when($this->type,                 $this->type),
            'data'            => $this->when($this->data,                 $this->data),
            'gender'          => $this->when($this->gender,               $this->gender),
            'created_at'      => $this->when($this->created_at,           $this->created_at?->format('Y-m-d H:i:s') . 'Z'),
            'updated_at'      => $this->when($this->updated_at,           $this->updated_at?->format('Y-m-d H:i:s') . 'Z'),

            // Relation
            'pricing'         => ServiceMasterPriceResource::collection($this->whenLoaded('pricing')),
            'service'         => ServiceResource::make($this->whenLoaded('service')),
            'master'          => UserResource::make($this->whenLoaded('master')),
            'shop'            => ShopResource::make($this->whenLoaded('shop')),
            'extras'          => ServiceExtraResource::collection($this->whenLoaded('extras')),
            'notifications'   => ServiceMasterNotificationResource::collection($this->whenLoaded('notifications'))
        ];
    }
}
