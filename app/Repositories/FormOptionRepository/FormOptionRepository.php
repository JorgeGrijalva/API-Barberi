<?php
declare(strict_types=1);

namespace App\Repositories\FormOptionRepository;

use App\Models\FormOption;
use App\Models\Language;
use App\Repositories\CoreRepository;
use Schema;

class FormOptionRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return FormOption::class;
    }

    /**
     * @param array $filter
     * @return mixed
     */
    public function paginate(array $filter): mixed
    {
        $locale = Language::where('default', 1)->first()?->locale;

        $column = $filter['column'] ?? 'id';

        if ($column !== 'id') {
            $column = Schema::hasColumn('form_options', $column) ? $column : 'id';
        }

        return $this->model()
            ->filter($filter)
            ->with([
                'translation' => fn($q) => $q
                    ->select('id', 'form_option_id', 'locale', 'title')
                    ->when($this->language, function ($q) use ($locale) {
                        $q->where(fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale));
                    })
            ])
            ->orderBy($column, $filter['sort'] ?? 'desc')
            ->paginate($filter['perPage'] ?? 10);
    }


    public function show(FormOption $formOption): FormOption
    {
        $locale  = Language::where('default', 1)->first()?->locale;

        return $formOption->loadMissing([
            'translation' => fn($q) => $q
                ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                }))
                ->select('id', 'form_option_id', 'locale', 'title', 'description'),
            'serviceMaster:id,service_id,master_id,shop_id',
            'serviceMaster.service.translation' => fn($q) => $q
                ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                }))
                ->select('id', 'service_id', 'locale', 'title', 'description'),
            'serviceMaster.master:id,firstname,lastname,email',
            'shop:id,uuid,slug,logo_img',
            'shop.translation' => fn($q) => $q
                ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                }))
                ->select('id', 'shop_id', 'locale', 'title', 'description'),
            'translations'
        ]);
    }
}
