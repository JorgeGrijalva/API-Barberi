<?php
declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\BookingExtra;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingExtraResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var BookingExtra|JsonResource $this */
        $locales = $this->relationLoaded('translations') ?
            $this->translations->pluck('locale')->toArray() : null;

        return [
            'id'                => $this->when($this->id,                $this->id),
            'booking_id'        => $this->when($this->booking_id,        $this->booking_id),
            'price'             => $this->when($this->price,             $this->price),
            'service_extra_id'  => $this->when($this->service_extra_id,  $this->service_extra_id),
            'created_at'        => $this->when($this->created_at, $this->created_at?->format('Y-m-d H:i:s') . 'Z'),
            'updated_at'        => $this->when($this->updated_at, $this->updated_at?->format('Y-m-d H:i:s') . 'Z'),

            //Relations
            'booking'           => BookingResource::make($this->whenLoaded('booking')),
            'serviceExtra'      => ServiceExtraResource::make($this->whenLoaded('serviceExtra')),
            'translation'       => TranslationResource::make($this->whenLoaded('translation')),
            'translations'      => TranslationResource::collection($this->whenLoaded('translations')),
            'locales'           => $this->when($locales, $locales),
        ];
    }
}
