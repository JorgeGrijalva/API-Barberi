<?php
declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductCompareResource extends JsonResource
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
            'min_qty'       => $this->when($this->min_qty,      $this->min_qty),
            'max_qty'       => $this->when($this->max_qty,      $this->max_qty),
            'min_price'     => $this->when($this->min_price,    $this->min_price),
            'max_price'     => $this->when($this->max_price,    $this->max_price),
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
            'properties'    => ProductPropertyResource::collection($this->properties),
            'stocks'        => StockResource::collection($this->stocks),
            'translation'   => TranslationResource::make($this->translation),
            'category'      => CategoryResource::make($this->category),
            'brand'         => BrandResource::make($this->brand),
        ];
    }

}
