<?php
declare(strict_types=1);

namespace App\Repositories\RegionRepository;

use App\Models\Language;
use App\Models\Region;
use App\Repositories\CoreRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Schema;

class RegionRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return Region::class;
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function paginate(array $filter): LengthAwarePaginator
    {
        $locale = Language::where('default', 1)->first()?->locale;

        $column = $filter['column'] ?? 'id';
        $sort   = $filter['sort'] ?? 'desc';

        if ($column !== 'id') {
            $column = Schema::hasColumn('regions', $column) ? $column : 'id';
        }

        return Region::filter($filter)
            ->with([
                'translation' => fn($query) => $query->when($locale, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
            ])
            ->when(data_get($filter, 'region_id'), function ($query, $id) use ($sort) {
                $query->orderByRaw(DB::raw("FIELD(id, $id) $sort"));
            },
                fn($q) => $q->orderBy($column, $sort)
            )
            ->paginate($filter['perPage'] ?? 10);
    }

    public function show(Region $model): Region
    {
        $locale = Language::where('default', 1)->first()?->locale;

        return $model->load([
            'translation' => fn($query) => $query->when($locale, fn($q) => $q->where(function ($q) use ($locale) {
                $q->where('locale', $this->language)->orWhere('locale', $locale);
            })),
            'translations',
        ]);
    }

}
