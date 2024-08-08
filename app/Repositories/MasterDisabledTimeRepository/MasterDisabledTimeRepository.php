<?php
declare(strict_types=1);

namespace App\Repositories\MasterDisabledTimeRepository;

use App\Models\Language;
use App\Models\MasterDisabledTime;
use App\Repositories\CoreRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Schema;

class MasterDisabledTimeRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return MasterDisabledTime::class;
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function paginate(array $filter = []): LengthAwarePaginator
    {
        $column = $filter['column'] ?? 'id';

        if ($column !== 'id') {
            $column = Schema::hasColumn('master_disabled_times', $column) ? $column : 'id';
        }

        $locale = Language::where('default', 1)->first()?->locale;

        return MasterDisabledTime::filter($filter)
            ->with([
                'master',
                'translations',
                'translation' => fn($q) => $q
                    ->select(['id', 'disabled_time_id', 'locale', 'title'])
                    ->when($this->language, function ($q) use ($locale) {
                        $q->where(fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale));
                    })
            ])
            ->orderBy($column, $filter['sort'] ?? 'desc')
            ->paginate($filter['perPage'] ?? 10);
    }

    /**
     * @param MasterDisabledTime $model
     * @return MasterDisabledTime
     */
    public function show(MasterDisabledTime $model): MasterDisabledTime
    {
        $locale = Language::where('default', 1)->first()?->locale;

        return $model->fresh([
            'master',
            'translations',
            'translation' => fn($q) => $q
                ->select(['id', 'disabled_time_id', 'locale', 'title'])
                ->when($this->language, function ($q) use ($locale) {
                    $q->where(fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale));
                })
        ]);
    }

}
