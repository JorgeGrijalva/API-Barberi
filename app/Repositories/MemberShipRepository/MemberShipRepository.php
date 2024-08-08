<?php
declare(strict_types=1);

namespace App\Repositories\MemberShipRepository;

use App\Models\MemberShip;
use App\Models\Language;
use App\Repositories\CoreRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Schema;

class MemberShipRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return MemberShip::class;
    }

    /**
     * @return array
     */
    private function getWith(): array
    {
        $locale = Language::where('default', 1)->first()?->locale;

        return [
            'galleries',
            'memberShipServices.service:id,slug',
            'memberShipServices.service.translation' => fn($query) => $query
                ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
            'translation' => fn($query) => $query
                ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
            'shop:id,uuid,slug,logo_img',
            'shop.translation' => fn($query) => $query
                ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
            'translations',
        ];
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
            $column = Schema::hasColumn('member_ships', $column) ? $column : 'id';
        }

        return MemberShip::filter($filter)
            ->withCount('memberShipServices')
            ->with([
                'translation' => fn($query) => $query
                    ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                        $q->where('locale', $this->language)->orWhere('locale', $locale);
                    })),
                'shop:id,uuid,slug,logo_img',
                'shop.translation' => fn($query) => $query
                    ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                        $q->where('locale', $this->language)->orWhere('locale', $locale);
                    })),
            ])
            ->orderBy($column, $filter['sort'] ?? 'desc')
            ->paginate($filter['perPage'] ?? 10);
    }

    /**
     * @param MemberShip $memberShip
     * @return MemberShip
     */
    public function show(MemberShip $memberShip): MemberShip
    {
        return $memberShip->fresh($this->getWith());
    }

    /**
     * @param int $id
     * @param int|null $shopId
     * @return Model|null
     */
    public function showById(int $id, ?int $shopId = null): ?Model
    {
        return $this->model()->with($this->getWith())->where(['id' => $id, 'shop_id' => $shopId])->first();
    }

}
