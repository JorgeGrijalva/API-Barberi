<?php
declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\UserAddress;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserAddressResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var UserAddress|JsonResource $this */
        return [
            'id'                    => $this->when($this->id,                   $this->id),
            'title'                 => $this->when($this->title,                $this->title),
            'user_id'               => $this->when($this->user_id,              $this->user_id),
            'active'                => $this->active,
            'address'               => $this->when($this->address,              $this->address),
            'location'              => $this->when($this->location,             $this->location),
            'firstname'             => $this->when($this->firstname,            $this->firstname),
            'lastname'              => $this->when($this->lastname,             $this->lastname),
            'phone'                 => $this->when($this->phone,                $this->phone),
            'zipcode'               => $this->when($this->zipcode,              $this->zipcode),
            'street_house_number'   => $this->when($this->street_house_number,  $this->street_house_number),
            'additional_details'    => $this->when($this->additional_details,   $this->additional_details),
            'region_id'             => $this->when($this->region_id,            $this->region_id),
            'country_id'            => $this->when($this->country_id,           $this->country_id),
            'city_id'               => $this->when($this->city_id,              $this->city_id),
            'area_id'               => $this->when($this->area_id,              $this->area_id),
            'created_at'            => $this->when($this->created_at,   $this->created_at?->format('Y-m-d H:i:s') . 'Z'),
            'updated_at'            => $this->when($this->updated_at,   $this->updated_at?->format('Y-m-d H:i:s') . 'Z'),

            'user'                  => UserResource::make($this->whenLoaded('user')),
            'orders'                => OrderResource::collection($this->whenLoaded('orders')),
            'region'                => RegionResource::make($this->whenLoaded('region')),
            'country'               => CountryResource::make($this->whenLoaded('country')),
            'city'                  => CityResource::make($this->whenLoaded('city')),
            'area'                  => AreaResource::make($this->whenLoaded('area')),
        ];
    }
}
