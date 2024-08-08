<?php
declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderReportResource extends JsonResource
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
        return [
            'id'                         => $this->when($this->id, $this->id),
            'user_id'                    => $this->when($this->user_id, $this->user_id),
            'total_price'                => $this->when($this->rate_total_price, $this->rate_total_price),
            'rate'                       => $this->when($this->rate, $this->rate),
            'order_details_count'        => $this->when($this->order_details_count, $this->order_details_count),
            'order_details_sum_quantity' => $this->when($this->order_details_sum_quantity, $this->order_details_sum_quantity),
            'tax'                        => $this->when($this->rate_total_tax, $this->rate_total_tax),
            'status'                     => $this->when($this->status, $this->status),
            'delivery_fee'               => $this->when($this->rate_delivery_fee, $this->rate_delivery_fee),
            'created_at'                 => $this->when($this->created_at, $this->created_at?->format('Y-m-d H:i:s') . 'Z'),
            'user'                       => UserResource::make($this->whenLoaded('user')),
            'details'                    => OrderDetailResource::collection($this->whenLoaded('orderDetails')),
        ];
    }
}
