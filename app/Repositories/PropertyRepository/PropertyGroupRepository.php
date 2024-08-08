<?php
declare(strict_types=1);

namespace App\Repositories\PropertyRepository;

use App\Models\Language;
use App\Models\PropertyGroup;
use App\Repositories\CoreRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Schema;

class PropertyGroupRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return PropertyGroup::class;
    }

    public function index(array $filter = []): LengthAwarePaginator
    {
        $locale = Language::where('default', 1)->first()?->locale;

        $column = $filter['column'] ?? 'id';

        if ($column !== 'id') {
            $column = Schema::hasColumn('property_groups', $column) ? $column : 'id';
        }

        return $this->model()
            ->with([
                'shop:id,uuid',
                'shop.translation' => fn($q) => $q
                    ->select('id', 'locale', 'title', 'shop_id')
                    ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
                'translation' => fn($q) => $q
                    ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                }))
                    ->when(data_get($filter, 'search'), fn ($q, $search) => $q->where('title', 'LIKE', "%$search%"))
            ])
            ->whereHas('translation', fn($q) => $q
                ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                }))
                ->when(data_get($filter, 'search'), fn ($q, $search) => $q->where('title', 'LIKE', "%$search%"))
            )
            ->when(data_get($filter, 'active'), fn($q, $active) => $q->where('active', $active))
            ->when(data_get($filter, 'shop_id'), fn($q, $shopId) => $q->where(function ($query) use ($shopId, $filter) {

                $query->where('shop_id', $shopId);

                if (!isset($filter['is_admin'])) {
                    $query->orWhereNull('shop_id');
                }

            }))
            ->orderBy($column, $filter['sort'] ?? 'desc')
            ->paginate($filter['perPage'] ?? 10);
    }

    public function show(int $id): Model|Collection|Builder|array|null
    {
        $locale = Language::where('default', 1)->first()?->locale;

        return $this->model()
            ->with([
                'shop:id,uuid',
                'shop.translation' => fn($q) => $q
                    ->select('id', 'locale', 'title', 'shop_id')
                    ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
                'translation' => fn($q) => $q
                    ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                }))
            ])
            ->whereHas(
                'translation',
                fn($query) => $query->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                }))
            )
            ->find($id);
    }

}
