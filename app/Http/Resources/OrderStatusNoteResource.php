<?php
declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\OrderStatusNote;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderStatusNoteResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  Request $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var OrderStatusNote|JsonResource $this */
        return [
            'id'         => $this->when($this->id,       $this->id),
            'order_id'   => $this->when($this->order_id, $this->order_id),
            'status'     => $this->when($this->status,   $this->status),
            'notes'      => $this->when($this->notes,    $this->notes),
            'created_at' => $this->when($this->created_at, $this->created_at?->format('Y-m-d H:i:s') . 'Z'),
            'updated_at' => $this->when($this->updated_at, $this->updated_at?->format('Y-m-d H:i:s') . 'Z'),

            // Relations
            'order'      => OrderResource::make($this->whenLoaded('order')),
        ];
    }
}
