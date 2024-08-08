<?php
declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\DigitalFile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DigitalFileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var DigitalFile|JsonResource $this */
        return [
            'id'            => $this->id,
            'active'        => (bool)$this->active,
            'product_id'    => $this->when($this->product_id, $this->product_id),
            'path'          => $this->when($this->path, $this->path),
            'created_at'    => $this->when($this->created_at, $this->created_at?->format('Y-m-d H:i:s') . 'Z'),
            'updated_at'    => $this->when($this->updated_at, $this->updated_at?->format('Y-m-d H:i:s') . 'Z'),

            // Relations
            'product'       => ProductResource::make($this->whenLoaded('product')),
            'user_digital'  => UserResource::make($this->whenLoaded('userDigital')),
            'users_digital' => UserResource::collection($this->whenLoaded('usersDigital')),
        ];
    }
}
