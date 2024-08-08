<?php
declare(strict_types=1);

namespace App\Repositories\BlogRepository;

use App\Helpers\Utility;
use App\Models\Blog;
use App\Models\Language;
use App\Repositories\CoreRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Schema;

class BlogRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return Blog::class;
    }

    /**
     * Get brands with pagination
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function blogsPaginate(array $filter = []): LengthAwarePaginator
    {
        $locale = Language::where('default', 1)->first()?->locale;
        $column = data_get($filter, 'column','id');

        if ($column !== 'id') {
            $column = Schema::hasColumn('blogs', $column) ? $column : 'id';
        }

        return $this->model()
            ->whereHas('translation', function ($q) use ($locale) {
                $q->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                }));
            })
            ->with([
                'translation' => fn($q) => $q
                    ->select('id', 'locale', 'blog_id', 'title', 'short_desc')
                    ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                        $q->where('locale', $this->language)->orWhere('locale', $locale);
                    }))
            ])
            ->when(data_get($filter, 'type'), function ($q, $type) {
                $q->where('type', data_get(Blog::TYPES, $type));
            })
            ->when(data_get($filter, 'active'), function ($q, $active) {
                $q->where('active', $active);
            })
            ->when(data_get($filter, 'published_at'), function ($q) {
                $q->whereNotNull('published_at');
            })
            ->orderBy($column, data_get($filter,'sort','desc'))
            ->paginate($filter['perPage'] ?? 10);
    }

    /**
     * Get brands with pagination
     */
    public function blogByUUID(string $uuid): Model|null
    {
        $locale = Language::where('default', 1)->first()?->locale;

        return $this->model()
            ->whereHas('translation', function ($q) use($locale) {
                $q->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                }));
            })
            ->with([
                'translation' => fn($q) => $q
                    ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                }))
            ])
            ->firstWhere('uuid', $uuid);
    }

    /**
     * Get brands with pagination
     */
    public function blogByID(int|string $id): Model|null
    {
        $locale = Language::where('default', 1)->first()?->locale;

        return $this->model()
            ->whereHas('translation', function ($q) use ($locale) {
                $q->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                }));
            })
            ->with([
                'translation' => fn($q) => $q
                    ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                }))
            ])
            ->find($id);
    }

    /**
     * @param int $id
     * @return array
     */
    public function reviewsGroupByRating(int $id): array
    {
        return Utility::reviewsGroupRating([
            'reviewable_type' => Blog::class,
            'reviewable_id'   => $id,
        ]);
    }
}
