<?php
declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\MasterClosedDate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MasterClosedDateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var MasterClosedDate|JsonResource $this */
        return [
            'id'         => $this->id,
            'date'       => $this->date,
            'master_id'  => $this->when($this->master_id,    $this->master_id),
            'created_at' => $this->when($this->created_at, $this->created_at?->format('Y-m-d H:i:s') . 'Z'),
            'updated_at' => $this->when($this->updated_at, $this->updated_at?->format('Y-m-d H:i:s') . 'Z'),

            'warehouse'  => WarehouseResource::make($this->whenLoaded('warehouse')),
        ];
    }
}
