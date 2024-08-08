<?php
declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BannerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var Banner|JsonResource $this */

        $locales = $this->relationLoaded('translations') ?
            $this->translations->pluck('locale')->toArray() : null;

        return [
            'id'            => (int)$this->id,
            'url'           => $this->url,
            'img'           => $this->img,
            'active'        => $this->active,
            'clickable'     => $this->clickable,
            'type'          => $this->type,
            'input'         => $this->input,
            'shop_id'       => $this->shop_id,
            'likes'         => $this->when($this->likes_count, $this->likes_count),
            'products_count'=> $this->when($this->products_count, $this->products_count),
            'created_at'    => $this->when($this->created_at, $this->created_at?->format('Y-m-d H:i:s') . 'Z'),
            'updated_at'    => $this->when($this->updated_at, $this->updated_at?->format('Y-m-d H:i:s') . 'Z'),

            // Relations
            'translation'   => TranslationResource::make($this->whenLoaded('translation')),
            'translations'  => TranslationResource::collection($this->whenLoaded('translations')),
            'locales'       => $this->when($locales, $locales),
            'shop'          => ShopResource::make($this->whenLoaded('shop')),
            'galleries'     => GalleryResource::collection($this->galleries),
            'products'      => ProductResource::collection($this->whenLoaded('products')),
        ];
    }
}
