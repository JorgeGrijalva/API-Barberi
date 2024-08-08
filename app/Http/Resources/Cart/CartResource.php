<?php
declare(strict_types=1);

namespace App\Http\Resources\Cart;

use App\Http\Resources\AreaResource;
use App\Http\Resources\CityResource;
use App\Http\Resources\CountryResource;
use App\Http\Resources\RegionResource;
use App\Http\Resources\UserResource;
use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var Cart|JsonResource $this */
        return [
            'id'            => $this->id,
            'owner_id'      => $this->owner_id,
            'status'        => $this->status,
            'total_price'   => $this->rate_total_price,
            'currency_id'   => $this->currency_id,
            'region_id'     => $this->when($this->region_id, $this->region_id),
            'country_id'    => $this->when($this->country_id, $this->country_id),
            'city_id'       => $this->when($this->city_id, $this->city_id),
            'area_id'       => $this->when($this->area_id, $this->area_id),
            'rate'          => $this->rate,
            'group'         => (bool)$this->group,
            'created_at'    => $this->when($this->created_at, $this->created_at?->format('Y-m-d H:i:s') . 'Z'),
            'updated_at'    => $this->when($this->updated_at, $this->updated_at?->format('Y-m-d H:i:s') . 'Z'),
            'user'          => UserResource::make($this->whenLoaded('user')),
            'user_carts'    => UserCartResource::collection($this->whenLoaded('userCarts')),
            'region'        => RegionResource::make($this->whenLoaded('region')),
            'country'       => CountryResource::make($this->whenLoaded('country')),
            'city'          => CityResource::make($this->whenLoaded('city')),
            'area'          => AreaResource::make($this->whenLoaded('area')),
        ];
    }
}
