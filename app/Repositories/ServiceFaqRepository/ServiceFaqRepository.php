<?php
declare(strict_types=1);

namespace App\Repositories\ServiceFaqRepository;

use App\Models\Language;
use App\Models\ServiceFaq;
use App\Repositories\CoreRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Schema;

class ServiceFaqRepository extends CoreRepository
{

    protected function getModelClass(): string
    {
        return ServiceFaq::class;
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function paginate(array $filter): LengthAwarePaginator
    {
        $locale = data_get(Language::where('default', 1)->first(), 'locale');

        $column = $filter['column'] ?? 'id';

        if ($column !== 'id') {
            $column = Schema::hasColumn('service_faqs', $column) ? $column : 'id';
        }

        return $this->model()
            ->filter($filter)
            ->with([
                'service:id,slug',
                'service.translation' => fn($query) => $query
                    ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                        $q->where('locale', $this->language)->orWhere('locale', $locale);
                    }))->select('id', 'service_id', 'locale', 'title'),
                'translation' => fn($query) => $query->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                    }))
                ])
            ->orderBy($column, $filter['sort'] ?? 'desc')
            ->paginate($filter['perPage'] ?? 10);
    }

    /**
     * @param ServiceFaq $serviceFaq
     * @return ServiceFaq
     */
    public function show(ServiceFaq $serviceFaq): ServiceFaq
    {
        $locale = data_get(Language::where('default', 1)->first(), 'locale');

        return $serviceFaq
            ->load([
                'service:id,slug',
                'service.translation' => fn($query) => $query
                    ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                        $q->where('locale', $this->language)->orWhere('locale', $locale);
                    }))->select('id', 'service_id', 'locale', 'title'),
                'translations',
                'translation' => fn($query) => $query
                    ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                        $q->where('locale', $this->language)->orWhere('locale', $locale);
                    }))
            ]);
    }
}
