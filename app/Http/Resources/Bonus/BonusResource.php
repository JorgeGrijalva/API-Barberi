<?php
declare(strict_types=1);

namespace App\Http\Resources\Bonus;

use App\Http\Resources\ShopResource;
use App\Http\Resources\StockResource;
use App\Models\Bonus;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BonusResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var Bonus|JsonResource $this */
        return [
            'id'                => $this->when($this->id, $this->id),
            'stock_id'          => $this->when($this->stock_id, $this->stock_id),
            'bonus_quantity'    => $this->when($this->bonus_quantity, $this->bonus_quantity),
            'bonus_stock_id'    => $this->when($this->bonus_stock_id, $this->bonus_stock_id),
            'value'             => $this->when($this->rate_value, $this->rate_value),
            'type'              => $this->when($this->type, $this->type),
            'shop_id'           => $this->when($this->shop_id, $this->shop_id),
            'status'            => (boolean)$this->status,
            'created_at'        => $this->when($this->created_at, optional($this->created_at)->format('Y-m-d H:i:s')),
            'updated_at'        => $this->when($this->updated_at, optional($this->updated_at)->format('Y-m-d H:i:s')),
            'expired_at'        => $this->when($this->expired_at, $this->expired_at->format('Y-m-d')),

            'bonusStock'        => StockResource::make($this->whenLoaded('bonusStock')),
            'stock'             => StockResource::make($this->whenLoaded('stock')),
            'shop'              => ShopResource::make($this->whenLoaded('shop')),
        ];
    }
}
