<?php
declare(strict_types=1);

namespace App\Http\Resources;

use App\Http\Resources\Bonus\BonusResource;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductStockResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var Stock|JsonResource $this */
        return [
            'id'                  => $this->id,
            'product_id'          => $this->when($this->product_id,             $this->product_id),
            'price'               => $this->when($this->rate_price,             $this->rate_price),
            'quantity'            => $this->when($this->quantity,               $this->quantity),
            'sku'                 => $this->when($this->sku,                    $this->sku),
            'bonus_expired_at'    => $this->when($this->bonus_expired_at,       $this->bonus_expired_at),
            'discount_expired_at' => $this->when($this->discount_expired_at,    $this->discount_expired_at),
            'discount'            => $this->when($this->rate_actual_discount,   $this->rate_actual_discount),
            'tax'                 => $this->when($this->rate_tax_price,         $this->rate_tax_price),
            'img'                 => $this->when($this->img,                    $this->img),
            'o_count'             => $this->when($this->o_count,                $this->o_count),
            'od_count'            => $this->when($this->od_count,               $this->od_count),
            'total_price'         => $this->when($this->rate_total_price,       $this->rate_total_price),

            // Relation
            'extras'              => StockExtraResource::collection($this->whenLoaded('stockExtras')),
            'bonus'               => BonusResource::make($this->whenLoaded('bonus')),
            'gallery'             => GalleryResource::make($this->whenLoaded('gallery')),
            'galleries'           => GalleryResource::collection($this->whenLoaded('galleries')),
            'whole_sale_prices'   => WholeSalePriceResource::collection($this->whenLoaded('wholeSalePrices')),
        ];
    }
}
