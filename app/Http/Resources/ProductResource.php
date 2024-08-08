<?php
declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var Product|JsonResource $this */
        $locales = $this->relationLoaded('translations') ?
            $this->translations->pluck('locale')->toArray() : null;

        return [
            'id'            => $this->when($this->id,           $this->id),
            'slug'          => $this->when($this->slug,         $this->slug),
            'uuid'          => $this->when($this->uuid,         $this->uuid),
            'shop_id'       => $this->when($this->shop_id,      $this->shop_id),
            'category_id'   => $this->when($this->category_id,  $this->category_id),
            'keywords'      => $this->when($this->keywords,     $this->keywords),
            'brand_id'      => $this->when($this->brand_id,     $this->brand_id),
            'tax'           => $this->when($this->tax,          $this->tax),
            'qr_code'       => $this->when($this->qr_code,      $this->qr_code),
            'status'        => $this->when($this->status,       $this->status),
            'status_note'   => $this->when($this->status_note,  $this->status_note),
            'min_qty'       => $this->min_qty ?? 0,
            'max_qty'       => $this->max_qty ?? 0,
            'min_price'     => $this->min_price ?? 0,
            'max_price'     => $this->max_price ?? 0,
            'active'        => (bool) $this->active,
            'visibility'    => (bool) $this->visibility,
            'digital'       => (bool) $this->digital,
            'img'           => $this->when($this->img, $this->img),
            'age_limit'     => $this->when($this->age_limit, $this->age_limit),
            'r_count'       => $this->when($this->r_count, $this->r_count),
            'r_avg'         => $this->when($this->r_avg, $this->r_avg),
            'r_sum'         => $this->when($this->r_sum, $this->r_sum),
            'o_count'       => $this->when($this->o_count, $this->o_count),
            'od_count'      => $this->when($this->od_count, $this->od_count),
            'interval'      => $this->when($this->interval, $this->interval),
            'created_at'    => $this->when($this->created_at, $this->created_at?->format('Y-m-d H:i:s') . 'Z'),
            'updated_at'    => $this->when($this->updated_at, $this->updated_at?->format('Y-m-d H:i:s') . 'Z'),

            // Relations
            'translation'   => TranslationResource::make($this->whenLoaded('translation')),
            'translations'  => TranslationResource::collection($this->whenLoaded('translations')),
            'locales'       => $this->when($locales, $locales),
            'properties'    => ProductPropertyResource::collection($this->whenLoaded('properties')),
            'stories'       => SimpleStoryResource::collection($this->whenLoaded('stories')),
            'shop'          => ShopResource::make($this->whenLoaded('shop')),
            'category'      => CategoryResource::make($this->whenLoaded('category')),
            'brand'         => BrandResource::make($this->whenLoaded('brand')),
            'unit'          => UnitResource::make($this->whenLoaded('unit')),
            'reviews'       => ReviewResource::collection($this->whenLoaded('reviews')),
            'galleries'     => GalleryResource::collection($this->galleries),
            'tags'          => TagResource::collection($this->whenLoaded('tags')),
            'meta_tags'     => MetaTagResource::collection($this->whenLoaded('metaTags')),
            'stock'         => ProductStockResource::make($this->whenLoaded('stock')),
            'stocks'        => ProductStockResource::collection($this->whenLoaded('stocks')),
            'digital_file'  => DigitalFileResource::make($this->whenLoaded('digitalFile')),
        ];
    }

}
