<?php
declare(strict_types=1);

namespace App\Http\Resources\Cart;

use App\Http\Resources\ShopResource;
use App\Models\CartDetail;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var CartDetail|JsonResource $this */
        return [
            'id'                    => $this->id,
            'shop_id'               => $this->shop_id,
            'updated_at'            => $this->updated_at,
            'shop_tax'              => $this->shop_tax,
            'discount'              => $this->discount,
            'total_price'           => $this->total_price,
            'shop'                  => ShopResource::make($this->whenLoaded('shop')),
            'cartDetailProducts'    => CartDetailProductResource::collection($this->whenLoaded('cartDetailProducts')),
        ];
    }
}
