<?php
declare(strict_types=1);

namespace App\Repositories\RequestModelRepository;

use App\Models\Category;
use App\Models\Language;
use App\Models\Product;
use App\Models\RequestModel;
use App\Repositories\CoreRepository;
use Schema;

class RequestModelRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return RequestModel::class;
    }

    /**
     * Get brands with pagination
     * @param array $filter
     * @return mixed
     */
    public function index(array $filter = []): mixed
    {
        $column = data_get($filter,'column','id');

        if ($column !== 'id') {
            $column = Schema::hasColumn('request_models', $column) ? $column : 'id';
        }

        return $this->model()
            ->filter($filter)
            ->with($this->getWithByType(data_get($filter, 'type', 'category')))
            ->orderBy($column, data_get($filter,'sort','desc'))
            ->paginate($filter['perPage'] ?? 10);
    }

    /**
     * @param RequestModel $requestModel
     * @return RequestModel
     */
    public function show(RequestModel $requestModel): RequestModel
    {
        return $requestModel->loadMissing($this->getOneWithByType($requestModel));
    }

    /**
     * @param RequestModel $requestModel
     * @return array|string[]
     */
    private function getOneWithByType(RequestModel $requestModel): array
    {
        $locale = Language::where('default', 1)->first()?->locale;

        $with = [
            'model',
            'createdBy',
        ];

        if ($requestModel->model_type === Product::class) {
            $with = [
                'model' => fn($q) => $q->with([
                    'galleries' => fn($q) => $q->select('id', 'type', 'loadable_id', 'path', 'title', 'preview'),
                    'properties' => fn($query) => $query->where(function ($q) use ($locale) {
                        $q->where('locale', $this->language)->orWhere('locale', $locale);
                    }),
                    'stocks.stockExtras.group.translation' => fn($query) => $query->where(function ($q) use ($locale) {
                        $q->where('locale', $this->language)->orWhere('locale', $locale);
                    }),
                    'stocks.addons.addon' => fn($q) => $q->where('active', true)
                        ->where('addon', true)
                        ->where('status', Product::PUBLISHED),
                    'stocks.addons.addon.stock',
                    'stocks.addons.addon.translation' => fn($query) => $query->where(function ($q) use ($locale) {
                        $q->where('locale', $this->language)->orWhere('locale', $locale);
                    }),
                    'discounts' => fn($q) => $q->where('start', '<=', today())->where('end', '>=', today())
                        ->where('active', 1),
                    'shop.translation' => fn($query) => $query->where(function ($q) use ($locale) {
                        $q->where('locale', $this->language)->orWhere('locale', $locale);
                    }),
                    'category' => fn($q) => $q->select('id', 'uuid'),
                    'category.translation' => fn($query) => $query->where(function ($q) use ($locale) {
                        $q->where('locale', $this->language)->orWhere('locale', $locale);
                    })
                        ->select('id', 'category_id', 'locale', 'title'),
                    'brand' => fn($q) => $q->select('id', 'uuid', 'title'),
                    'unit.translation' => fn($query) => $query->where(function ($q) use ($locale) {
                        $q->where('locale', $this->language)->orWhere('locale', $locale);
                    }),
                    'reviews.galleries',
                    'reviews.user',
                    'translation' => fn($query) => $query->where(function ($q) use ($locale) {
                        $q->where('locale', $this->language)->orWhere('locale', $locale);
                    }),
                    'tags.translation' => fn($q) => $q->select('id', 'category_id', 'locale', 'title')
                        ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                            $q->where('locale', $this->language)->orWhere('locale', $locale);
                        })),
                ]),
                'createdBy',
            ];
        } else if ($requestModel->model_type === Category::class) {
            $with = [
                'model.translation' => fn($q) => $q
                    ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                        $q->where('locale', $this->language)->orWhere('locale', $locale);
                    })),
                'createdBy',
            ];
        }

        return $with;
    }

    /**
     * @param string|null $type
     * @return array|string[]
     */
    private function getWithByType(?string $type = null): array
    {
        $locale = Language::where('default', 1)->first()?->locale;

        $with = [
            'model',
            'createdBy',
        ];

        if (in_array($type, ['category', 'product'])) {
            $with = [
                'model.translation' => fn($q) => $q
                    ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                        $q->where('locale', $this->language)->orWhere('locale', $locale);
                    })),
                'createdBy',
            ];
        }

        return $with;
    }
}
