<?php
declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\FormOption;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FormOptionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var FormOption|JsonResource $this */
        return [
            'id'                => $this->when($this->id,                 $this->id),
            'shop_id'           => $this->when($this->shop_id,            $this->shop_id),
            'service_master_id' => $this->when($this->service_master_id,  $this->service_master_id),
            'required'          => $this->when($this->required,           $this->required),
            'active'            => $this->when($this->active,             $this->active),
            'data'              => $this->when($this->data,               $this->data),

            // Relations
            'shop'           => ShopResource::make($this->whenLoaded('shop')),
            'service_master' => ServiceMasterResource::make($this->whenLoaded('serviceMaster')),
            'translation'    => TranslationResource::make($this->whenLoaded('translation')),
            'translations'   => TranslationResource::collection($this->whenLoaded('translations')),
        ];
    }
}
