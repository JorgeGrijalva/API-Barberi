<?php
declare(strict_types=1);

namespace App\Repositories\ShopSubscriptionRepository;

use App\Models\Language;
use App\Models\ShopSocial;
use App\Models\ShopSubscription;
use App\Repositories\CoreRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ShopSubscriptionRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return ShopSocial::class;
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function paginate(array $filter): LengthAwarePaginator
    {
        $locale = Language::where('default', 1)->first()?->locale;

        return ShopSubscription::filter($filter)
            ->with([
                'subscription',
                'transaction',
                'shop.translation' => fn($query) => $query
                    ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                        $q->where('locale', $this->language)->orWhere('locale', $locale);
                    })),
            ])
            ->paginate($filter['perPage'] ?? 10);
    }

    /**
     * @param ShopSubscription $shopSubscription
     * @return ShopSubscription
     */
    public function show(ShopSubscription $shopSubscription): ShopSubscription
    {
        $locale = Language::where('default', 1)->first()?->locale;

        return $shopSubscription->load([
            'subscription',
            'transaction',
            'shop.translation' => fn($query) => $query
                ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
        ]);
    }
}
