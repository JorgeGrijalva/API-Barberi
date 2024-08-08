<?php

namespace App\Imports;

use App\Models\Translation;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class TranslationImport extends BaseImport implements ToCollection, WithHeadingRow, WithBatchInserts
{
    use Importable;

    /**
     * @param Collection $collection
     * @return void
     */
    public function collection(Collection $collection): void
    {
        foreach ($collection as $row) {

            $key = $row['key'] ?? null;

            if (!$key) {
                continue;
            }

            unset($row['key']);

            foreach ($row as $locale => $value) {

                if (empty($value)) {
                    continue;
                }

                Translation::updateOrCreate([
                    'key'    => $key,
                    'locale' => $locale,
                ], [
                    'value'  => $value,
                    'group'  => 'web',
                ]);

            }

        }
    }

    public function headingRow(): int
    {
        return 1;
    }

    public function batchSize(): int
    {
        return 200;
    }

    public function chunkSize(): int
    {
        return 200;
    }
}
