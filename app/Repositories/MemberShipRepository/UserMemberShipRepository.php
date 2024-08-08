<?php
declare(strict_types=1);

namespace App\Repositories\MemberShipRepository;

use App\Models\Language;
use App\Models\UserMemberShip;
use App\Repositories\CoreRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Schema;

class UserMemberShipRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return UserMemberShip::class;
    }

    /**
     * @return array
     */
    private function getWith(): array
    {
        $locale = Language::where('default', 1)->first()?->locale;

        return [
            'user:id,firstname,lastname,img',
            'transaction.paymentSystem',
            'memberShip.galleries',
            'memberShip.memberShipServices.service:id,slug',
            'memberShip.memberShipServices.service.translation' => fn($query) => $query
                ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
            'memberShip.translation' => fn($query) => $query
                ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
            'memberShip.translations',
            'memberShip.shop:id,uuid,slug,logo_img,user_id',
            'memberShip.shop.translation' => fn($query) => $query
                ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
        ];
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function paginate(array $filter = []): LengthAwarePaginator
    {
        $column = $filter['column'] ?? 'id';

        if ($column !== 'id') {
            $column = Schema::hasColumn('user_member_ships', $column) ? $column : 'id';
        }

        return UserMemberShip::filter($filter)
            ->with($this->getWith())
            ->orderBy($column, $filter['sort'] ?? 'desc')
            ->paginate($filter['perPage'] ?? 10);
    }

    /**
     * @param UserMemberShip $model
     * @return UserMemberShip
     */
    public function show(UserMemberShip $model): UserMemberShip
    {
        return $model->fresh($this->getWith());
    }

    /**
     * @param int $id
     * @param int|null $shopId
     * @return Model|null
     */
    public function showById(int $id, ?int $shopId = null): ?Model
    {
        return $this->model()
            ->with($this->getWith())
            ->where('id', $id)
            ->when($shopId, fn($q) => $q->whereHas('memberShip', fn($q) => $q->where('shop_id', $shopId)))
            ->first();
    }

}
