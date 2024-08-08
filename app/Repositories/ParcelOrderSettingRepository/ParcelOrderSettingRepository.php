<?php
declare(strict_types=1);

namespace App\Repositories\ParcelOrderSettingRepository;

use App\Models\Language;
use App\Models\ParcelOrderSetting;
use App\Repositories\CoreRepository;
use Illuminate\Contracts\Pagination\Paginator;
use Schema;

class ParcelOrderSettingRepository extends CoreRepository
{
    /**
     * @return string
     */
    protected function getModelClass(): string
    {
        return ParcelOrderSetting::class;
    }

    /**
     * @param array $filter
     * @return Paginator
     */
    public function restPaginate(array $filter = []): Paginator
    {
        /** @var ParcelOrderSetting $model */
        $model  = $this->model();
        $locale = Language::where('default', 1)->first()?->locale;

        return $model
            ->filter($filter)
            ->with([
                'parcelOptions.translation' => fn($query) => $query->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                }))
            ])
            ->paginate($filter['perPage'] ?? 10);
    }

    /**
     * @param array $filter
     * @return Paginator
     */
    public function paginate(array $filter = []): Paginator
    {
        /** @var ParcelOrderSetting $model */
        $model = $this->model();

        return $model
            ->filter($filter)
            ->paginate($filter['perPage'] ?? 10);
    }

    /**
     * @param ParcelOrderSetting $parcelOrderSetting
     * @return ParcelOrderSetting
     */
    public function show(ParcelOrderSetting $parcelOrderSetting): ParcelOrderSetting
    {
        $locale = Language::where('default', 1)->first()?->locale;

        return $parcelOrderSetting
            ->loadMissing([
                'parcelOptions.translation' => fn($query) => $query->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                }))
            ]);
    }

    /**
     * @param int $id
     * @return ParcelOrderSetting|null
     */
    public function showById(int $id): ?ParcelOrderSetting
    {
        $parcelOrderSetting = ParcelOrderSetting::find($id);

        $locale = Language::where('default', 1)->first()?->locale;

        return $parcelOrderSetting
            ?->loadMissing([
                'parcelOptions.translation' => fn($query) => $query->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                }))
            ]);
    }
}
