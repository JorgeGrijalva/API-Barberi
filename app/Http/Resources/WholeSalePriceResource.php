<?php
declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\WholeSalePrice;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WholeSalePriceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var WholeSalePrice|JsonResource $this */
        return [
            'id'            => $this->id,
            'min_quantity'  => $this->when($this->min_quantity, $this->min_quantity),
            'max_quantity'  => $this->when($this->max_quantity, $this->max_quantity),
            'price'         => $this->when($this->rate_price,   $this->rate_price),
        ];
    }
}
