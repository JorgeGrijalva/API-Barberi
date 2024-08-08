<?php
declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Area;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AreaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var Area|JsonResource $this */
        $locales = $this->relationLoaded('translations') ?
            $this->translations->pluck('locale')->toArray() : null;

        return [
            'id'            => $this->id,
            'active'        => (bool)$this->active,
            'region_id'     => $this->when($this->region_id,  $this->region_id),
            'country_id'    => $this->when($this->country_id, $this->country_id),
            'city_id'       => $this->when($this->city_id,    $this->city_id),

            // Relations
            'translation'   => TranslationResource::make($this->whenLoaded('translation')),
            'translations'  => TranslationResource::collection($this->whenLoaded('translations')),
            'locales'       => $this->when($locales, $locales),
            'region'        => RegionResource::make($this->whenLoaded('region')),
            'country'       => CountryResource::make($this->whenLoaded('country')),
            'city'          => CityResource::make($this->whenLoaded('city')),
        ];
    }
}
