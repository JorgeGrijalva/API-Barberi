<?php
declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BrandResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var Brand|JsonResource $this */
        return [
            'id'                => $this->id,
            'active'            => $this->active,
            'slug'              => $this->when($this->slug,  $this->slug),
            'uuid'              => $this->when($this->uuid,  $this->uuid),
            'title'             => $this->when($this->title, $this->title),
            'img'               => $this->when($this->img, $this->img),
            'shop_id'           => $this->when($this->shop_id, $this->shop_id),
            'products_count'    => $this->when($this->products_count, $this->products_count),
            'created_at'        => $this->when($this->created_at, $this->created_at?->format('Y-m-d H:i:s') . 'Z'),
            'updated_at'        => $this->when($this->updated_at, $this->updated_at?->format('Y-m-d H:i:s') . 'Z'),

            'shop'              => ShopResource::make($this->whenLoaded('shop')),
            'meta_tags'         => MetaTagResource::collection($this->whenLoaded('metaTags')),
            'logs'              => ModelLogResource::collection($this->whenLoaded('logs')),
        ];
    }
}
