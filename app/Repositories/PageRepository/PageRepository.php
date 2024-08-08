<?php
declare(strict_types=1);

namespace App\Repositories\PageRepository;

use App\Models\Language;
use App\Models\Page;
use App\Repositories\CoreRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

class PageRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return Page::class;
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function paginate(array $filter): LengthAwarePaginator
    {
        $locale = Language::where('default', 1)->first()?->locale;

        /** @var Page $model */
        $model = $this->model();

        if (data_get($filter, 'type') === Page::ALL_ABOUT) {
            unset($filter['type']);
            $filter['types'] = [Page::ABOUT, Page::ABOUT_SECOND, Page::ABOUT_THREE];
        }

        return $model
            ->filter($filter)
            ->with([
                'translations',
                'translation' => fn($query) => $query->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
            ])
            ->paginate($filter['perPage'] ?? 10);
    }

    /**
     * @param Page $model
     * @return Page|null
     */
    public function show(Page $model): Page|null
    {
        $locale = Language::where('default', 1)->first()?->locale;

        return $model->loadMissing([
            'galleries',
            'translations',
            'translation' => fn($query) => $query->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
        ]);
    }

    /**
     * @param string $type
     * @return Model|null
     */
    public function showByType(string $type): ?Model
    {
        $locale = Language::where('default', 1)->first()?->locale;

        return Page::with([
            'galleries',
            'translations',
            'translation' => fn($query) => $query->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
        ])
            ->where('type', $type)
            ->first();

    }
}
