<?php
declare(strict_types=1);

namespace App\Repositories\BonusRepository;

use App\Models\Bonus;
use App\Models\Language;
use App\Repositories\CoreRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BonusRepository extends CoreRepository
{
    /**
     * @return string
     */
    protected function getModelClass(): string
    {
        return Bonus::class;
    }

    public function paginate(array $filter): LengthAwarePaginator
    {

        /** @var Bonus $bonus */
        $bonus = $this->model();
        $locale = Language::where('default', 1)->first()?->locale;

        return $bonus
            ->whereShopId(data_get($filter, 'shop_id'))
            ->when(data_get($filter, 'type'),
                fn($query, $type) => $query->where('type', $type === 'product' ? 'count' : 'sum')
            )
            ->with([
                'shop' => fn($q) => $q->select(['id', 'uuid']),
                'shop.translation' => fn($q) => $q
                    ->select('id', 'locale', 'title', 'shop_id')
                    ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
                'stock.product' => fn($q) => $q->select(['id', 'uuid']),
                'stock.product.translation' => fn($q) => $q
                    ->select('id', 'locale', 'title', 'product_id')
                    ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
                'bonusStock.product' => fn($q) => $q->select(['id', 'uuid']),
                'bonusStock.product.translation' => fn($q) => $q
                    ->select('id', 'locale', 'title', 'product_id')
                    ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
            ])
            ->orderByDesc('id')
            ->paginate($filter['perPage'] ?? 10);
    }


    /**
     * Get one brands by Identification number
     */
    public function show(Bonus $bonus): Bonus
    {
        $locale = Language::where('default', 1)->first()?->locale;

        return $bonus->load([
            'stock.product' => fn($q) => $q->select(['id', 'uuid']),
            'stock.stockExtras.value',
            'stock.stockExtras.group.translation' => fn($q) => $q
                ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
            'stock.product.translation' => fn($q) => $q
                ->select('id', 'locale', 'title', 'product_id')
                ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
            'bonusStock.product' => fn($q) => $q->select(['id', 'uuid']),
            'bonusStock.product.translation' => fn($q) => $q
                ->select('id', 'locale', 'title', 'product_id')
                ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
            'shop' => fn($q) => $q->select(['id', 'uuid']),
            'shop.translation' => fn($q) => $q
                ->select('id', 'locale', 'title', 'shop_id')
                ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
        ]);
    }

}
