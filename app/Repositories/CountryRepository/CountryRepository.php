<?php
declare(strict_types=1);

namespace App\Repositories\CountryRepository;

use App\Models\Country;
use App\Models\Language;
use App\Repositories\CoreRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Schema;

class CountryRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return Country::class;
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
            $column = Schema::hasColumn('countries', $column) ? $column : 'id';
        }

        return Country::filter($filter)
            ->with([
                'translation' => fn($query) => $query->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
            ])
            ->withCount([
                'cities' => fn($q) => $q->when(data_get($filter, 'has_price'),  fn($q) => $q->whereHas('deliveryPrice'))
            ])
            ->when(data_get($filter, 'country_id'), function ($query, $id) use ($sort) {
                    $query->orderByRaw(DB::raw("FIELD(id, $id) $sort"));
                },
                fn($q) => $q->orderBy($column, $sort)
            )
            ->paginate($filter['perPage'] ?? 10);
    }

    /**
     * @param Country $model
     * @return Country
     */
    public function show(Country $model): Country
    {
        $locale = Language::where('default', 1)->first()?->locale;

        return $model->load([
            'galleries',
            'region.translation' => fn($query) => $query
                ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
            'translation' => fn($query) => $query
                ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
            'translations',
            'city.translation' => fn($query) => $query
                ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
        ]);
    }

    /**
     * @param int $id
     * @param array $filter
     * @return Builder|Model|null
     */
    public function checkCountry(int $id, array $filter): Builder|Model|null
    {
        $city = data_get($filter, 'city');
        $locale = Language::where('default', 1)->first()?->locale;

        return Country::with([
            'city.translation' => fn($q) => $q
                ->where('title', 'like', "%$city%")
                ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                }))
        ])
            ->whereHas('city.translation', function ($query) use ($city, $locale) {
                $query
                    ->where('title', 'like', "%$city%")
                    ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                        $q->where('locale', $this->language)->orWhere('locale', $locale);
                    }));
            })
            ->firstWhere('id', $id);
    }
}
