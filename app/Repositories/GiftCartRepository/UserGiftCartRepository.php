<?php
declare(strict_types=1);

namespace App\Repositories\GiftCartRepository;

use App\Models\Language;
use App\Models\UserGiftCart;
use App\Repositories\CoreRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Schema;

class UserGiftCartRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return UserGiftCart::class;
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
                'giftCart.translation' => fn($q) => $q
                    ->select('id', 'gift_cart_id', 'locale', 'title')
                    ->when($this->language, function ($q) use ($locale) {
                        $q->where(fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale));
                    }),
                'transaction.paymentSystem'
            ])
            ->paginate($filter['perPage'] ?? 10);
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function paginate(array $filter): LengthAwarePaginator
    {
        $locale = Language::where('default', 1)->first()?->locale;

        return $this->model()
            ->filter($filter)
            ->with([
                'giftCart.translation' => fn($q) => $q
                    ->select('id', 'gift_cart_id', 'locale', 'title')
                    ->when($this->language, function ($q) use ($locale) {
                        $q->where(fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale));
                    }),
                'user' => fn($q) => $q->select(['id', 'uuid', 'firstname', 'lastname', 'img', 'active']),
                'transaction.paymentSystem'
            ])
            ->paginate($filter['perPage'] ?? 10);
    }

    /**
     * @param UserGiftCart $userGiftCart
     * @return UserGiftCart
     */
    public function show(UserGiftCart $userGiftCart): UserGiftCart
    {
        $locale = Language::where('default', 1)->first()?->locale;

        return $userGiftCart->load([
            'giftCart.translation' => fn($q) => $q
                ->when($this->language, function ($q) use ($locale) {
                    $q->where(fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale));
                }),
            'user' => fn($q) => $q->select(['id', 'uuid', 'firstname', 'lastname', 'img', 'active']),
            'transaction.paymentSystem'
        ]);
    }

}
