<?php
declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var City|JsonResource $this */
        $locales = $this->relationLoaded('translations') ?
            $this->translations->pluck('locale')->toArray() : null;

        return [
            'id'            => $this->id,
            'active'        => (bool)$this->active,
            'region_id'     => $this->when($this->region_id,  $this->region_id),
            'country_id'    => $this->when($this->country_id, $this->country_id),

            // Relations
            'translation'   => TranslationResource::make($this->whenLoaded('translation')),
            'translations'  => TranslationResource::collection($this->whenLoaded('translations')),
            'locales'       => $this->when($locales, $locales),
            'country'       => CountryResource::make($this->whenLoaded('country')),
            'region'        => RegionResource::make($this->whenLoaded('region')),
            'area'          => AreaResource::make($this->whenLoaded('area')),
        ];
    }
}
