<?php
declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use App\Models\ServiceMasterPrice;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceMasterPriceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var ServiceMasterPrice|JsonResource $this */
        $locales = $this->relationLoaded('translations') ?
            $this->translations->pluck('locale')->toArray() : null;

        return [
            'id'                => $this->when($this->id,                $this->id),
            'service_master_id' => $this->when($this->service_master_id, $this->service_master_id),
            'duration'          => $this->when($this->duration,          $this->duration),
            'price_type'        => $this->when($this->price_type,        $this->price_type),
            'price'             => $this->when($this->price,             $this->price),
            'smart'             => $this->when($this->smart,             $this->smart),
            'created_at'        => $this->when($this->created_at, $this->created_at?->format('Y-m-d H:i:s') . 'Z'),
            'updated_at'        => $this->when($this->updated_at, $this->updated_at?->format('Y-m-d H:i:s') . 'Z'),

            // Relations
            'service_master'    => ServiceMasterResource::make($this->whenLoaded('serviceMaster')),
            'translation'       => TranslationResource::make($this->whenLoaded('translation')),
            'translations'      => TranslationResource::collection($this->whenLoaded('translations')),
            'locales'           => $this->when($locales, $locales),
        ];
    }
}
