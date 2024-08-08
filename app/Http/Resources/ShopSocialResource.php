<?php
declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ShopSocial;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShopSocialResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var ShopSocial|JsonResource $this */

        return [
            'id'            => $this->id,
            'shop_id'       => $this->when($this->shop_id,  $this->shop_id),
            'type'          => $this->when($this->type,     $this->type),
            'content'       => $this->when($this->content,  $this->content),
            'img'           => $this->when($this->img,      $this->img),
            'created_at'    => $this->when($this->created_at, $this->created_at?->format('Y-m-d H:i:s') . 'Z'),
            'updated_at'    => $this->when($this->updated_at, $this->updated_at?->format('Y-m-d H:i:s') . 'Z'),

            // Relations
            'shop'          => ShopResource::make($this->whenLoaded('shop'))
        ];
    }
}
