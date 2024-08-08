<?php
declare(strict_types=1);

namespace App\Repositories\ServiceMasterNotificationRepository;

use App\Models\Language;
use App\Repositories\CoreRepository;
use App\Models\ServiceMasterNotification;
use Illuminate\Pagination\LengthAwarePaginator;
use Schema;

class ServiceMasterNotificationRepository extends CoreRepository
{

    protected function getModelClass(): string
    {
        return ServiceMasterNotification::class;
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function paginate(array $filter = []): LengthAwarePaginator
    {
        $locale = data_get(Language::where('default', 1)->first(), 'locale');
        $column = $filter['column'] ?? 'id';

        if ($column !== 'id') {
            $column = Schema::hasColumn('service_master_notifications', $column) ? $column : 'id';
        }

        return $this->model()
            ->with([
                'serviceMaster.master',
                'translation' => fn($q) => $q
                    ->when($this->language, function ($q) use ($locale) {
                        $q->where(fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale));
                    })
            ])
            ->filter($filter)
            ->orderBy($column, $filter['sort'] ?? 'desc')
            ->paginate($filter['perPage'] ?? 10);
    }

    /**
     * @param ServiceMasterNotification $serviceMasterNotification
     * @return ServiceMasterNotification
     */
    public function show(ServiceMasterNotification $serviceMasterNotification): ServiceMasterNotification
    {
        $locale = data_get(Language::where('default', 1)->first(), 'locale');

        return $serviceMasterNotification
            ->load([
                'serviceMaster.master',
                'serviceMaster.service:id',
                'serviceMaster.service.translation' => fn($q) => $q
                    ->when($this->language, function ($q) use ($locale) {
                        $q->where(fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale));
                    }),
                'translation' => fn($q) => $q
                    ->when($this->language, function ($q) use ($locale) {
                        $q->where(fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale));
                    }),
                'translations'
            ]);
    }
}
