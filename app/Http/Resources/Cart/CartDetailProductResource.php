<?php
declare(strict_types=1);

namespace App\Http\Resources\Cart;

use App\Http\Resources\GalleryResource;
use App\Http\Resources\StockResource;
use App\Models\CartDetailProduct;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartDetailProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var CartDetailProduct|JsonResource $this */
        return [
            'id'            => $this->id,
            'quantity'      => $this->quantity,
            'bonus'         => $this->bonus,
            'price'         => $this->rate_price,
            'discount'      => $this->rate_discount,
            'updated_at'    => $this->updated_at,
            'stock'         => StockResource::make($this->whenLoaded('stock')),
            'parent'        => CartDetailProductResource::make($this->whenLoaded('parent')),
            'gallery'       => GalleryResource::make($this->whenLoaded('gallery')),
            'galleries'     => GalleryResource::collection($this->whenLoaded('galleries')),
        ];
    }
}
