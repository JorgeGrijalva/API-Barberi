<?php
declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ShopLocation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShopLocationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var ShopLocation|JsonResource $this */
        return [
            'id'            => $this->when($this->id, $this->id),
            'shop_id'       => $this->when($this->shop_id, $this->shop_id),
            'region_id'     => $this->when($this->region_id, $this->region_id),
            'country_id'    => $this->when($this->country_id, $this->country_id),
            'city_id'       => $this->when($this->city_id, $this->city_id),
            'area_id'       => $this->when($this->area_id, $this->area_id),
            'type'          => $this->when($this->type, $this->type),
            'created_at'    => $this->when($this->created_at, $this->created_at->format('Y-m-d H:i:s') . 'Z'),
            'updated_at'    => $this->when($this->updated_at, $this->updated_at->format('Y-m-d H:i:s') . 'Z'),

            // Relations
            'region'        => RegionResource::make($this->whenLoaded('region')),
            'country'       => CountryResource::make($this->whenLoaded('country')),
            'city'          => CityResource::make($this->whenLoaded('city')),
            'area'          => AreaResource::make($this->whenLoaded('area')),
            'shop'          => ShopResource::make($this->whenLoaded('shop')),
        ];
    }
}
