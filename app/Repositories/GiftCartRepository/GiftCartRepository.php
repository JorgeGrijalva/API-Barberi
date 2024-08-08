<?php
declare(strict_types=1);

namespace App\Repositories\GiftCartRepository;

use App\Models\GiftCart;
use App\Models\Language;
use App\Repositories\CoreRepository;
use Schema;

class GiftCartRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return GiftCart::class;
    }

    /**
     * @param array $filter
     * @return mixed
     */
    public function paginate(array $filter): mixed
    {
        $locale = Language::where('default', 1)->first()?->locale;

        return $this->model()
            ->filter($filter)
            ->with([
                'shop:id,uuid,slug,logo_img,user_id',
                'shop.translation' => fn($query) => $query
                    ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                        $q->where('locale', $this->language)->orWhere('locale', $locale);
                    })),
                'translation' => fn($q) => $q
                    ->select('id', 'gift_cart_id', 'locale', 'title')
                    ->when($this->language, function ($q) use ($locale) {
                        $q->where(fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale));
                    }),
            ])
            ->paginate($filter['perPage'] ?? 10);
    }

    /**
     * @param array $filter
     * @return mixed
     */
    public function myGiftCarts(array $filter): mixed
    {
        $locale = Language::where('default', 1)->first()?->locale;

        return $this->model()
            ->filter($filter)
            ->with([
                'shop:id,uuid,slug,logo_img,user_id',
                'shop.translation' => fn($query) => $query
                    ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                        $q->where('locale', $this->language)->orWhere('locale', $locale);
                    })),
                'translation' => fn($q) => $q
                    ->select('id', 'gift_cart_id', 'locale', 'title')
                    ->when($this->language, function ($q) use ($locale) {
                        $q->where(fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale));
                    }),
            ])
            ->paginate($filter['perPage'] ?? 10);
    }

    public function show(GiftCart $giftCart): GiftCart
    {
        $locale  = Language::where('default', 1)->first()?->locale;

        return $giftCart->loadMissing([
            'shop:id,uuid,slug,logo_img,user_id',
            'shop.translation' => fn($query) => $query
                ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
            'translation' => fn($q) => $q
                ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                }))
                ->select('id', 'gift_cart_id', 'locale', 'title'),
            'translations'
        ]);
    }
}
