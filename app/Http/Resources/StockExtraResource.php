<?php
declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\StockExtra;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockExtraResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var StockExtra|JsonResource $this */
        return [
            'id'                => $this->id,
            'stock_id'          => $this->stock_id,
            'extra_value_id'    => $this->extra_value_id,
            'extra_group_id'    => $this->extra_group_id,

            // Relation
            'value'             => ExtraValueResource::make($this->whenLoaded('value')),
            'group'             => ExtraGroupResource::make($this->whenLoaded('group')),
        ];
    }
}
