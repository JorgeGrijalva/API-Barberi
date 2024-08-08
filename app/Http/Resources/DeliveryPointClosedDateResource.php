<?php
declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\DeliveryPointClosedDate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryPointClosedDateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var DeliveryPointClosedDate|JsonResource $this */
        return [
            'id'                => $this->id,
            'date'              => $this->date,
            'delivery_point_id' => $this->when($this->delivery_point_id, $this->delivery_point_id),
            'created_at'        => $this->when($this->created_at, $this->created_at?->format('Y-m-d H:i:s') . 'Z'),
            'updated_at'        => $this->when($this->updated_at, $this->updated_at?->format('Y-m-d H:i:s') . 'Z'),

            'deliveryPoint'     => DeliveryPointResource::make($this->whenLoaded('deliveryPoint')),
        ];
    }
}
