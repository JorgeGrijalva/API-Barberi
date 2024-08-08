<?php

namespace App\Http\Resources;

use App\Http\Resources\Bonus\SimpleBonusResource;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderStockResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var Stock|JsonResource $this */
        return [
            'id'                => $this->id,
            'countable_id'      => $this->when($this->product_id, $this->product_id),
            'price'             => $this->when($this->rate_price, $this->rate_price),
            'quantity'          => $this->when($this->quantity, $this->quantity),
            'discount'          => $this->when($this->rate_actual_discount, (double) $this->rate_actual_discount),
            'tax'               => $this->when($this->rate_tax_price, $this->rate_tax_price),
            'total_price'       => $this->when($this->rate_total_price, $this->rate_total_price),
            'deleted_at'        => $this->when($this->deleted_at, $this->deleted_at?->format('Y-m-d H:i:s') . 'Z'),

            // Relation
            'extras'            => StockExtraResource::collection($this->whenLoaded('stockExtras')),
            'product'           => ProductResource::make($this->whenLoaded('product')),
            'bonus'             => SimpleBonusResource::make($this->whenLoaded('bonus')),
        ];
    }
}
