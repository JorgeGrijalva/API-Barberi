<?php
declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\PropertyValue;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyValueResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var PropertyValue|JsonResource $this */
        return [
            'id'                => $this->when($this->id,                   $this->id),
            'img'               => $this->when($this->img,                  $this->img),
            'extra_group_id'    => $this->when($this->property_group_id,    $this->property_group_id),
            'value'             => $this->when($this->value,                $this->value),
            'active'            => $this->when($this->active,               $this->active),

            // Relations
            'group'             => PropertyGroupResource::make($this->whenLoaded('group')),
            'galleries'         => GalleryResource::collection($this->whenLoaded('galleries')),
        ];
    }
}
