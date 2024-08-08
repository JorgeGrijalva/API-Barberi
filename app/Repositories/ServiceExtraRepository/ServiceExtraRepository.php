<?php
declare(strict_types=1);

namespace App\Repositories\ServiceExtraRepository;

use App\Models\Language;
use App\Models\ServiceExtra;
use App\Repositories\CoreRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Schema;

class ServiceExtraRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return ServiceExtra::class;
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function paginate(array $filter = []): LengthAwarePaginator
    {
        $locale = Language::where('default', 1)->first()?->locale;

        $column = $filter['column'] ?? 'id';

        if ($column !== 'id') {
            $column = Schema::hasColumn('service_extras', $column) ? $column : 'id';
        }

        /** @var ServiceExtra $serviceExtra */
        $serviceExtra = $this->model();

        return $serviceExtra
            ->filter($filter)
            ->with([
                'service.translation' => fn($q) => $q
                    ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                        $q->where('locale', $this->language)->orWhere('locale', $locale);
                    }))
                    ->select('id', 'locale', 'title', 'service_id'),
                'shop:id,logo_img',
                'shop.translation' => fn($q) => $q
                    ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                        $q->where('locale', $this->language)->orWhere('locale', $locale);
                    }))
                    ->select('id', 'shop_id', 'locale', 'title'),
                'translation' => fn($q) => $q
                    ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                        $q->where('locale', $this->language)->orWhere('locale', $locale);
                    })),
                'translations',
            ])
            ->orderBy($column, $filter['sort'] ?? 'desc')
            ->paginate($filter['perPage'] ?? 10);
    }

    /**
     * @param ServiceExtra $serviceExtra
     * @return ServiceExtra
     */
    public function show(ServiceExtra $serviceExtra):ServiceExtra
    {
        $locale = Language::where('default', 1)->first()?->locale;

        return $serviceExtra
            ->load([
                'service.translation' => fn($q) => $q
                    ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                        $q->where('locale', $this->language)->orWhere('locale', $locale);
                    }))
                    ->select('id', 'locale', 'title', 'service_id'),
                'shop:id,logo_img',
                'shop.translation' => fn($q) => $q
                    ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                        $q->where('locale', $this->language)->orWhere('locale', $locale);
                    }))
                    ->select('id', 'shop_id', 'locale', 'title'),
                'translation' => fn($q) => $q
                    ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                        $q->where('locale', $this->language)->orWhere('locale', $locale);
                    })),
                'translations'
            ]);
    }
}
