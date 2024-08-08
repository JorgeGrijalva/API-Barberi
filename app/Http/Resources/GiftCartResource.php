<?php
declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\GiftCart;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GiftCartResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var GiftCart|JsonResource $this */
        return [
            'id'      => $this->when($this->id,      $this->id),
            'shop_id' => $this->when($this->shop_id, $this->shop_id),
            'active'  => $this->when($this->active,  $this->active),
            'price'   => $this->when($this->price,   $this->price),
            'time'    => $this->when($this->time,    $this->time),

            // Relations
            'shop'         => ShopResource::make($this->whenLoaded('shop')),
            'translation'  => TranslationResource::make($this->whenLoaded('translation')),
            'translations' => TranslationResource::collection($this->whenLoaded('translations')),
        ];
    }
}
