<?php
declare(strict_types=1);

namespace App\Repositories\DeliveryPriceRepository;

use App\Models\DeliveryPrice;
use App\Models\Language;
use App\Repositories\CoreRepository;
use App\Traits\ByLocation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DeliveryPriceRepository extends CoreRepository
{
    use ByLocation;

    protected function getModelClass(): string
    {
        return DeliveryPrice::class;
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function paginate(array $filter): LengthAwarePaginator
    {
        $locale = Language::where('default', 1)->first()?->locale;

        return DeliveryPrice::filter($filter)
            ->with([
                    'shop:id,logo_img',
                    'translation' => fn($query) => $query
                        ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                            $q->where('locale', $this->language)->orWhere('locale', $locale);
                        })),
                    'shop.translation' => fn($q) => $q
                        ->select(['id', 'shop_id', 'locale', 'title'])
                        ->when($this->language, function ($q) use ($locale) {
                            $q->where(fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale));
                        })
                ] + $this->getWith())
            ->paginate($filter['perPage'] ?? 10);
    }

    /**
     * @param DeliveryPrice $model
     * @return DeliveryPrice
     */
    public function show(DeliveryPrice $model): DeliveryPrice
    {
        $locale = Language::where('default', 1)->first()?->locale;

        return $model
            ->load([
                'shop:id,logo_img',
                'translation' => fn($query) => $query
                    ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                        $q->where('locale', $this->language)->orWhere('locale', $locale);
                    })),
                'shop.translation' => fn($q) => $q
                    ->select(['id', 'shop_id', 'locale', 'title'])
                    ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                        $q->where('locale', $this->language)->orWhere('locale', $locale);
                    }))
            ] + $this->getWith());
    }

}
