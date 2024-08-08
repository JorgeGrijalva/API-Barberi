<?php
declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Language;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LanguageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var Language|JsonResource $this */
        return [
            'id'        => $this->id,
            'title'     => $this->title,
            'locale'    => $this->locale,
            'backward'  => (bool)$this->backward,
            'default'   => (bool)$this->default,
            'active'    => (bool)$this->active,
            'img'       => $this->img,
        ];
    }
}
