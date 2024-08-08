<?php
declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\UserDigitalFile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserDigitalFileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var UserDigitalFile|JsonResource $this */
        return [
            'id'                => $this->id,
            'active'            => (bool)$this->active,
            'downloaded'        => (bool)$this->downloaded,
            'digital_file_id'   => $this->when($this->digital_file_id, $this->digital_file_id),
            'user_id'           => $this->when($this->user_id, $this->user_id),
            'created_at'        => $this->when($this->created_at, $this->created_at?->format('Y-m-d H:i:s') . 'Z'),
            'updated_at'        => $this->when($this->updated_at, $this->updated_at?->format('Y-m-d H:i:s') . 'Z'),

            // Relations
            'digital_file'      => $this->relationLoaded('digitalFile') ? [
                'id'                => $this->digitalFile?->id,
                'active'            => (bool)$this->digitalFile?->active,
                'product_id'        => $this->digitalFile?->product_id,
                'product'           => $this->when($this->digitalFile?->product?->id, ProductResource::make($this->digitalFile?->product))
            ] : [],
        ];
    }
}
