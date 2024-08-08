<?php
declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ExtraGroup;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExtraGroupResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var ExtraGroup|JsonResource $this */
        $locales = $this->relationLoaded('translations') ?
            $this->translations->pluck('locale')->toArray() : null;

        return [
            'id'            => $this->id,
            'type'          => (string) $this->type,
            'active'        => (bool) $this->active,
            'shop_id'       => $this->when($this->shop_id, $this->shop_id),

            // Relation
            'translation'   => TranslationResource::make($this->whenLoaded('translation')),
            'translations'  => TranslationResource::collection($this->whenLoaded('translations')),
            'extra_values'  => ExtraValueResource::collection($this->whenLoaded('extraValues')),
            'shop'  		=> ShopResource::make($this->whenLoaded('shop')),
            'locales'       => $this->when($locales, $locales),
        ];
    }
}
