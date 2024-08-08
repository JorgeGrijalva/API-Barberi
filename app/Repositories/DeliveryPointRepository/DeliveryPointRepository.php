<?php
declare(strict_types=1);

namespace App\Repositories\DeliveryPointRepository;

use App\Models\DeliveryPoint;
use App\Models\Language;
use App\Repositories\CoreRepository;
use App\Traits\ByLocation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DeliveryPointRepository extends CoreRepository
{
    use ByLocation;

    protected function getModelClass(): string
    {
        return DeliveryPoint::class;
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function paginate(array $filter = []): LengthAwarePaginator
    {
        $locale = Language::where('default', 1)->first()?->locale;

        return DeliveryPoint::filter($filter)
            ->with([
                'translation' => fn($query) => $query->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
                'workingDays',
                'closedDates',
            ] + $this->getWith())
            ->whereHas(
                'translation',
                fn($query) => $query->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                }))
            )
            ->paginate($filter['perPage'] ?? 10);
    }

    /**
     * @param DeliveryPoint $deliveryPoint
     * @return DeliveryPoint
     */
    public function show(DeliveryPoint $deliveryPoint): DeliveryPoint
    {
        return $this->loadShow($deliveryPoint);
    }

    /**
     * @param int $id
     * @return DeliveryPoint|null
     */
    public function showById(int $id): ?DeliveryPoint
    {
        $model = DeliveryPoint::find($id);

        if (!$model) {
            return null;
        }

        return $this->loadShow($model);
    }

    private function loadShow(DeliveryPoint $model): DeliveryPoint
    {
        $locale = Language::where('default', 1)->first()?->locale;

        return $model->loadMissing([
            'galleries',
            'workingDays',
            'closedDates',
            'translation' => fn($query) => $query->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
            'translations'
        ] + $this->getWith());
    }

}
