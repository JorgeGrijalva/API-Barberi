<?php
declare(strict_types=1);

namespace App\Repositories\ParcelOptionRepository;

use App\Models\Language;
use App\Models\ParcelOption;
use App\Repositories\CoreRepository;
use Illuminate\Contracts\Pagination\Paginator;
use Schema;

class ParcelOptionRepository extends CoreRepository
{
    /**
     * @return string
     */
    protected function getModelClass(): string
    {
        return ParcelOption::class;
    }

    /**
     * @param array $filter
     * @return Paginator
     */
    public function paginate(array $filter = []): Paginator
    {
        /** @var ParcelOption $model */
        $model  = $this->model();
        $column = $filter['column'] ?? 'id';

        if ($column !== 'id') {
            $column = Schema::hasColumn('parcel_options', $column) ? $column : 'id';
        }

        $locale = Language::where('default', 1)->first()?->locale;

        return $model
            ->filter($filter)
            ->with([
                'translation' => fn($query) => $query->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                }))
            ])
            ->orderBy($column, $filter['sort'] ?? 'desc')
            ->paginate($filter['perPage'] ?? 10);
    }

    /**
     * @param ParcelOption $parcelOption
     * @return ParcelOption
     */
    public function show(ParcelOption $parcelOption): ParcelOption
    {
        $locale = Language::where('default', 1)->first()?->locale;

        return $parcelOption
            ->loadMissing([
                'translations',
                'translation' => fn($query) => $query->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                }))
            ]);
    }

    /**
     * @param int $id
     * @return ParcelOption|null
     */
    public function showById(int $id): ?ParcelOption
    {
        $parcelOption = ParcelOption::find($id);

        $locale = Language::where('default', 1)->first()?->locale;

        return $parcelOption?->loadMissing([
            'translations',
            'translation' => fn($query) => $query->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                }))
        ]);
    }

}
