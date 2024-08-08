<?php

namespace App\Http\Resources;

use App\Models\OrderDetail;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var OrderDetail|JsonResource $this */
        return [
            'id'                => $this->when($this->id, $this->id),
            'order_id'          => $this->when($this->order_id, $this->order_id),
            'stock_id'          => $this->when($this->stock_id, $this->stock_id),
            'replace_stock_id'  => $this->when($this->replace_stock_id, $this->replace_stock_id),
            'replace_quantity'  => $this->when($this->replace_quantity, $this->replace_quantity),
            'replace_note'      => $this->when($this->replace_note, $this->replace_note),
            'total_price'       => $this->when($this->rate_total_price, $this->rate_total_price),
            'tax'               => $this->when($this->rate_tax, $this->rate_tax),
            'quantity'          => $this->when($this->quantity, $this->quantity),
            'note'              => $this->when($this->note, $this->note),
            'bonus'             => (bool)$this->bonus,
            'created_at'        => $this->when($this->created_at, $this->created_at?->format('Y-m-d H:i:s') . 'Z'),
            'updated_at'        => $this->when($this->updated_at, $this->updated_at?->format('Y-m-d H:i:s') . 'Z'),

            // Relations
            'stock'         => OrderStockResource::make($this->whenLoaded('stock')),
            'replace_stock' => StockResource::make($this->whenLoaded('replaceStock')),
            'gallery'       => GalleryResource::make($this->whenLoaded('gallery')),
            'galleries'     => GalleryResource::collection($this->whenLoaded('galleries')),
        ];
    }
}
