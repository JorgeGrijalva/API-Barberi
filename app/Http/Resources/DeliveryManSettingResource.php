<?php
declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\DeliveryManSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryManSettingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var DeliveryManSetting|JsonResource $this */

        return [
            'id'                => $this->id,
            'user_id'           => $this->user_id,
            'type_of_technique' => $this->type_of_technique,
            'brand'             => $this->brand,
            'model'             => $this->model,
            'number'            => $this->number,
            'color'             => $this->color,
            'online'            => (boolean)$this->online,
            'location'          => $this->location,
            'created_at'        => $this->when($this->created_at, $this->created_at?->format('Y-m-d H:i:s') . 'Z'),
            'updated_at'        => $this->when($this->updated_at, $this->updated_at?->format('Y-m-d H:i:s') . 'Z'),

            // Relations
            'deliveryman'   => UserResource::make($this->whenLoaded('deliveryman')),
            'galleries'     => GalleryResource::collection($this->whenLoaded('galleries')),
            'region'        => RegionResource::make($this->whenLoaded('region')),
            'country'       => CountryResource::make($this->whenLoaded('country')),
            'city'          => CityResource::make($this->whenLoaded('city')),
            'area'          => AreaResource::make($this->whenLoaded('area')),
        ];
    }
}
