<?php

namespace App\Http\Resources;

use App\Models\ServiceMasterNotification;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceMasterNotificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var ServiceMasterNotification|JsonResource $this */
        $locales = $this->relationLoaded('translations') ?
            $this->translations->pluck('locale')->toArray() : null;

        return [
            'id'                => $this->when($this->id,                   $this->id),
            'service_master_id' => $this->when($this->service_master_id,    $this->service_master_id),
            'notification_time' => $this->when($this->notification_time,    $this->notification_time),
            'notification_type' => $this->when($this->notification_type,    $this->notification_type),
            'last_sent_at'      => $this->when($this->last_sent_at,         $this->last_sent_at),
            'created_at'        => $this->when($this->created_at,           $this->created_at?->format('Y-m-d H:i:s') . 'Z'),
            'updated_at'        => $this->when($this->updated_at,           $this->updated_at?->format('Y-m-d H:i:s') . 'Z'),

            // Relations
            'service_master'    => ServiceMasterResource::make($this->whenLoaded('serviceMaster')),
            'translation'       => TranslationResource::make($this->whenLoaded('translation')),
            'translations'      => TranslationResource::collection($this->whenLoaded('translations')),
            'locales'           => $this->when($locales, $locales),
        ];
    }
}
