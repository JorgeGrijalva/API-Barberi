<?php

namespace App\Exports;

use App\Models\Language;
use App\Models\Translation;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TranslationExport extends BaseExport implements FromCollection, WithHeadings
{
    public function __construct(private ?Collection $languages = null, private array $data = [])
    {
        $this->languages = Language::pluck('locale');
    }

    /**
    * @return Collection
    */
    public function collection(): Collection
    {
        $translations = Translation::orderBy('id')->get();

        $translations->map(fn (Translation $translation) => $this->tableBody($translation));

        return collect($this->data)->values();

    }

    /**
     * @return string[]
     */
    public function headings(): array
    {
        $headers = [
            'key'
        ];

        foreach ($this->languages as $language) {
            $headers[] = $language;
        }

        return $headers;
    }

    /**
     * @param Translation $translation
     * @return array
     */
    private function tableBody(Translation $translation): array
    {
        if (!isset($this->data[$translation->key])) {
            $this->data[$translation->key] = [
                'key' => $translation->key,
            ];
        }

        if (!in_array($translation->locale, $this->languages->toArray())) {
            $this->languages[] = $translation->locale;
        }

        foreach ($this->languages as $language) {

            if ($language == $translation->locale) {
                $this->data[$translation->key][$language] = $translation->value;
            }

        }

        return [];
    }
}
