<?php
declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Translation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TranslationResource extends JsonResource
{
    /**
     * Ключи не трогать т.к общий ресурс для всех таблиц table_translations
     * Transform the resource into an array.
     *
     * @param  Request $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var Translation|JsonResource $this */
        return [
            'id'          => (int) $this->id,
            'locale'      => (string) $this->locale,
            'title'       => $this->when($this->title,        (string) $this->title),
            'short_desc'  => $this->when($this->short_desc,   (string) $this->short_desc),
            'description' => $this->when($this->description,  (string) $this->description),
            'button_text' => $this->when($this->button_text,  (string) $this->button_text),
            'term'        => $this->when($this->term,         (string) $this->term),
            'address'     => $this->when($this->address,      (string) $this->address),
            'question'    => $this->when($this->question,     (string) $this->question),
            'answer'      => $this->when($this->answer,       (string) $this->answer),
            'faq'         => $this->when($this->faq,          (string) $this->faq),
        ];
    }
}
