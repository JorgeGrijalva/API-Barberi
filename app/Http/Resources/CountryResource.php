<?php
declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CountryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var Country|JsonResource $this */
        $locales = $this->relationLoaded('translations') ?
            $this->translations->pluck('locale')->toArray() : null;

        return [
            'id'            => $this->id,
            'code'          => $this->code,
            'active'        => (bool)$this->active,
            'region_id'     => $this->when($this->region_id, $this->region_id),
            'img'           => $this->when($this->img, $this->img),
            'cities_count'  => $this->when($this->cities_count, $this->cities_count),

            // Relations
            'translation'   => TranslationResource::make($this->whenLoaded('translation')),
            'translations'  => TranslationResource::collection($this->whenLoaded('translations')),
            'locales'       => $this->when($locales, $locales),
            'region'        => RegionResource::make($this->whenLoaded('region')),
            'city'          => CityResource::make($this->whenLoaded('city')),
            'cities'        => CityResource::collection($this->whenLoaded('cities')),
        ];
    }
}
