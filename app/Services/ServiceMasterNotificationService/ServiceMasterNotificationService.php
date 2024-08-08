<?php
declare(strict_types=1);

namespace App\Services\ServiceMasterNotificationService;

use Throwable;
use App\Services\CoreService;
use App\Helpers\ResponseError;
use App\Traits\SetTranslations;
use App\Models\ServiceMasterNotification;

class ServiceMasterNotificationService extends CoreService
{
    use SetTranslations;

    /**
     * @return string
     */
    protected function getModelClass(): string
    {
        return ServiceMasterNotification::class;
    }

    /**
     * @param array $data
     * @return array
     */
    public function create(array $data): array
    {

        try {
            $model = $this->model()->create($data);

            $this->setTranslations($model, $data);

            return [
                'status' => true,
                'code'   => ResponseError::NO_ERROR,
                'data'   => $model->load('serviceMaster')
            ];

        } catch (Throwable $e) {
            return [
                'status'  => false,
                'code'    => $e->getCode() ? 'ERROR_' . $e->getCode() : ResponseError::ERROR_400,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * @param ServiceMasterNotification $model
     * @param array $data
     * @return array
     */
    public function update(ServiceMasterNotification $model, array $data): array
    {
        try {
            $model->update($data);

            $this->setTranslations($model, $data);

            return [
                'status' => true,
                'code'   => ResponseError::NO_ERROR,
                'data'   => $model->load('serviceMaster')
            ];

        } catch (Throwable $e) {
            return [
                'status'  => false,
                'code'    => $e->getCode() ? 'ERROR_' . $e->getCode() : ResponseError::ERROR_400,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * @param array $ids
     * @param int|null $shopId
     * @param int|null $masterId
     * @return void
     */
    public function delete(array $ids = [], ?int $shopId = null, ?int $masterId = null): void
    {
        $models = $this->model()
            ->whereIn('id', $ids)
            ->when($shopId, fn($q, $shopId) => $q->whereHas('serviceMaster', fn($q) => $q
                    ->where('shop_id', $shopId)))
            ->when($masterId, fn($q, $masterId) => $q->whereHas('serviceMaster', fn($q) => $q
                    ->where('master_id', $masterId)))
            ->get();

        foreach ($models as $model) {
            $model->delete();
        }
    }
}
