<?php
declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WarehouseResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  Request $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var Warehouse|JsonResource $this */
        return [
            'id'            => $this->when($this->id,            $this->id),
            'active'        => $this->when($this->active,        $this->active),
            'region_id'     => $this->when($this->region_id,     $this->region_id),
            'country_id'    => $this->when($this->country_id,    $this->country_id),
            'city_id'       => $this->when($this->city_id,       $this->city_id),
            'area_id'       => $this->when($this->area_id,       $this->area_id),
            'address'       => $this->when($this->address,       $this->address),
            'location'      => $this->when($this->location,      $this->location),
            'img'           => $this->when($this->img,           $this->img),
            'created_at'    => $this->when($this->created_at, $this->created_at?->format('Y-m-d H:i:s') . 'Z'),
            'updated_at'    => $this->when($this->updated_at, $this->updated_at?->format('Y-m-d H:i:s') . 'Z'),

            'translation'   => TranslationResource::make($this->whenLoaded('translation')),
            'translations'  => TranslationResource::collection($this->whenLoaded('translations')),
            'region'        => RegionResource::make($this->whenLoaded('region')),
            'country'       => CountryResource::make($this->whenLoaded('country')),
            'city'          => CityResource::make($this->whenLoaded('city')),
            'area'          => AreaResource::make($this->whenLoaded('area')),
            'working_days'  => WarehouseWorkingDayResource::collection($this->whenLoaded('workingDays')),
            'closed_date'   => WarehouseClosedDateResource::collection($this->whenLoaded('closedDates')),
        ];
    }
}
