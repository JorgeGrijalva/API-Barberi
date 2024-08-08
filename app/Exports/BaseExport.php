<?php
declare(strict_types=1);

namespace App\Exports;

use Illuminate\Support\Collection;

class BaseExport
{
    /**
     * @param Collection $galleries
     * @param string $column
     * @return string
     */
    protected function imageUrl(Collection $galleries, string $column = 'path'): string
    {
        return $galleries->transform(function ($gallery) use ($column) {
            return [
                $column => data_get($gallery, $column)
            ];
        })->implode($column, ',');
    }
}
