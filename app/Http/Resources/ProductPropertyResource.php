<?php
declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ProductProperty;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductPropertyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var ProductProperty|JsonResource $this */
        return [
            'id'                => $this->id,
            'product_id'        => $this->product_id,
            'property_group_id' => $this->property_group_id,
            'property_value_id' => $this->property_value_id,

            'product'           => ProductResource::make($this->whenLoaded('product')),
            'group'             => PropertyGroupResource::make($this->whenLoaded('group')),
            'value'             => PropertyValueResource::make($this->whenLoaded('value')),
        ];
    }
}
