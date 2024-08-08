<?php
declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\PropertyGroup;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyGroupResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var PropertyGroup|JsonResource $this */
        $locales = $this->relationLoaded('translations') ?
            $this->translations->pluck('locale')->toArray() : null;

        return [
            'id'                => $this->when($this->id, $this->id),
            'type'              => $this->when($this->type, $this->type),
            'shop_id'           => $this->when($this->shop_id, $this->shop_id),
            'active'            => (bool)$this->active,

            // Relation
            'translation'       => TranslationResource::make($this->whenLoaded('translation')),
            'translations'      => TranslationResource::collection($this->whenLoaded('translations')),
            'values'            => PropertyValueResource::collection($this->whenLoaded('propertyValues')),
            'shop'              => ShopResource::make($this->whenLoaded('shop')),
            'locales'           => $this->when($locales, $locales),
        ];
    }
}
