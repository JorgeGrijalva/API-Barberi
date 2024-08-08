<?php
declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\MasterDisabledTime;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MasterDisabledTimeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var MasterDisabledTime|JsonResource $this */
         $locales = $this->relationLoaded('translations') ?
            $this->translations->pluck('locale')->toArray() : null;

        return [
            'id'                    => $this->when($this->id,                   $this->id),
            'master_id'             => $this->when($this->master_id,            $this->master_id),
            'repeats'               => $this->when($this->repeats,              $this->repeats),
            'custom_repeat_type'    => $this->when($this->custom_repeat_type,   $this->custom_repeat_type),
            'custom_repeat_value'   => $this->when($this->custom_repeat_value,  $this->custom_repeat_value),
            'date'                  => $this->when($this->date,                 $this->date),
            'from'                  => $this->when($this->from,                 $this->from),
            'to'                    => $this->when($this->to,                   $this->to),
            'end_type'              => $this->when($this->end_type,             $this->end_type),
            'end_value'             => $this->when($this->end_value,            $this->end_value),
            'can_booking'           => $this->when($this->can_booking,          $this->can_booking),
            'created_at'            => $this->when($this->created_at,   $this->created_at?->format('Y-m-d H:i:s') . 'Z'),
            'updated_at'            => $this->when($this->updated_at,   $this->updated_at?->format('Y-m-d H:i:s') . 'Z'),

            /* Relations */
            'master'                => UserResource::make($this->whenLoaded('master')),
            'translation'           => TranslationResource::make($this->whenLoaded('translation')),
            'translations'          => TranslationResource::collection($this->whenLoaded('translations')),
            'locales'               => $this->when($locales, $locales),
        ];
    }
}
