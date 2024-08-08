<?php
declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\MemberShip;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberShipResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var MemberShip|JsonResource $this */
        return [
            'id'             => $this->when($this->id,             $this->id),
            'shop_id'        => $this->when($this->shop_id,        $this->shop_id),
            'active'         => (bool) $this->active,
            'color'          => $this->when($this->color,          $this->color),
            'price'          => $this->when($this->price,          $this->price),
            'time'           => $this->when($this->time,           $this->time),
            'sessions'       => $this->when($this->sessions,       $this->sessions),
            'sessions_count' => $this->when($this->sessions_count, $this->sessions_count),
            'services_count' => $this->when($this->member_ship_services_count, $this->member_ship_services_count),
            'created_at'     => $this->when($this->created_at, $this->created_at->format('Y-m-d H:i:s') . 'Z'),
            'updated_at'     => $this->when($this->updated_at, $this->updated_at->format('Y-m-d H:i:s') . 'Z'),

            // Relations
            'service'        => $this->whenLoaded('memberShipService'),
            'services'       => $this->whenLoaded('memberShipServices'),
            'translation'    => TranslationResource::make($this->whenLoaded('translation')),
            'translations'   => TranslationResource::collection($this->whenLoaded('translations')),
            'galleries'      => GalleryResource::collection($this->whenLoaded('galleries')),
            'shop'           => ShopResource::make($this->whenLoaded('shop')),
        ];
    }
}
