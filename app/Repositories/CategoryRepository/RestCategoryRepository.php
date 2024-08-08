<?php
declare(strict_types=1);

namespace App\Repositories\CategoryRepository;

use App\Models\Category;
use App\Models\Language;
use App\Repositories\CoreRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class RestCategoryRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return Category::class;
    }

    /**
     * Get Parent, only categories where parent_id == 0
     */
    public function parentCategories(array $filter = []): LengthAwarePaginator
    {
        /** @var Category $category */
        $category = $this->model();
        $locale = Language::where('default', 1)->first()?->locale;

        return $category
            ->withThreeChildren(['lang' => $this->language])
            ->filter($filter)
            ->whereHas('translation',
                fn($q) => $q
                    ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                        $q->where('locale', $this->language)->orWhere('locale', $locale);
                    }))
                    ->select('id', 'locale', 'title', 'category_id'),
            )
            ->paginate($filter['perPage'] ?? 10);
    }

}
