<?php
declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ServiceExtra;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceExtraResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var ServiceExtra|JsonResource $this */
        $locales = $this->relationLoaded('translations') ?
            $this->translations->pluck('locale')->toArray() : null;

        return [
            'id'           => $this->when($this->id,         $this->id),
            'service_id'   => $this->when($this->service_id, $this->service_id),
            'shop_id'      => $this->when($this->shop_id,    $this->shop_id),
            'active'       => (bool) $this->active,
            'price'        => $this->when($this->price,      $this->price),
            'img'          => $this->when($this->img,        $this->img),
            'created_at'   => $this->when($this->created_at, $this->created_at?->format('Y-m-d H:i:s') . 'Z'),
            'updated_at'   => $this->when($this->updated_at, $this->updated_at?->format('Y-m-d H:i:s') . 'Z'),

            //Relations
            'service'      => ServiceResource::make($this->whenLoaded('service')),
            'shop'         => ShopResource::make($this->whenLoaded('shop')),
            'translation'  => TranslationResource::make($this->whenLoaded('translation')),
            'translations' => TranslationResource::collection($this->whenLoaded('translations')),
            'locales'      => $this->when($locales, $locales),
        ];
    }
}
