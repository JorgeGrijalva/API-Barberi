<?php
declare(strict_types=1);

namespace App\Repositories\ShopSocialRepository;

use App\Models\Language;
use App\Models\ShopSocial;
use App\Repositories\CoreRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ShopSocialRepository extends CoreRepository
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
        return ShopSocial::filter($filter)->paginate($filter['perPage'] ?? 10);
    }

    /**
     * @param ShopSocial $social
     * @return ShopSocial
     */
    public function show(ShopSocial $social): ShopSocial
    {
        $locale = Language::where('default', 1)->first()?->locale;

        return $social->loadMissing([
            'shop.translation' => fn($query) => $query->where(
                fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale)
            )
        ]);
    }

    /**
     * @param  int  $shopId
     *
     * @return Collection|array
     */
    public function socialByShop(int $shopId): Collection|array
    {
        return ShopSocial::where('shop_id', $shopId)->get();
    }
}
