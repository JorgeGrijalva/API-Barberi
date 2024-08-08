<?php
declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\DeliveryPrice;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryPriceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var DeliveryPrice|JsonResource $this */
        return [
            'id'            => $this->id,
            'price'         => $this->when($this->price,      $this->price),
            'region_id'     => $this->when($this->region_id,  $this->region_id),
            'country_id'    => $this->when($this->country_id, $this->country_id),
            'city_id'       => $this->when($this->city_id,    $this->city_id),
            'area_id'       => $this->when($this->area_id,    $this->area_id),
            'shop_id'       => $this->when($this->shop_id,    $this->shop_id),

            // Relations
            'translation'   => TranslationResource::make($this->whenLoaded('translation')),
            'translations'  => TranslationResource::collection($this->whenLoaded('translations')),
            'region'        => RegionResource::make($this->whenLoaded('region')),
            'country'       => CountryResource::make($this->whenLoaded('country')),
            'city'          => CityResource::make($this->whenLoaded('city')),
            'area'          => AreaResource::make($this->whenLoaded('area')),
            'shop'          => ShopResource::make($this->whenLoaded('shop')),
        ];
    }
}
