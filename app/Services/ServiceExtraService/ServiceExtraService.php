<?php
declare(strict_types=1);

namespace App\Services\ServiceExtraService;

use App\Helpers\ResponseError;
use App\Models\ServiceExtra;
use App\Services\CoreService;
use App\Traits\SetTranslations;
use Throwable;

class ServiceExtraService extends CoreService
{
    use SetTranslations;

    protected function getModelClass(): string
    {
        return ServiceExtra::class;
    }

    /**
     * @param array $data
     * @return array
     */
    public function create(array $data): array
    {
        try {
            $serviceExtra = $this->model()->create($data);
            $this->setTranslations($serviceExtra, $data);

            if (isset($data['img'])) {
                $serviceExtra->uploads([$data['img']]);
            }

            return [
                'status' => true,
                'code' => ResponseError::NO_ERROR,
                'data' => $serviceExtra->loadMissing(['translations'])
            ];

        } catch (Throwable $e) {
            return [
                'status' => false,
                'code' => ResponseError::ERROR_400,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * @param ServiceExtra $serviceExtra
     * @param array $data
     * @return array
     */
    public function update(ServiceExtra $serviceExtra, array $data): array
    {
        try {
            $serviceExtra->update($data);
            $this->setTranslations($serviceExtra, $data);

            if (isset($data['img'])) {
                $serviceExtra->uploads([$data['img']]);
            }

            return [
                'status' => true,
                'code' => ResponseError::NO_ERROR,
                'data' => $serviceExtra->loadMissing(['translations'])
            ];

        } catch (Throwable $e) {
            return [
                'status' => false,
                'code' => ResponseError::ERROR_502,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * @param array|null $ids
     * @param array $filter
     * @return void
     */
    public function delete(?array $ids = [], array $filter = []): void
    {
        $models = ServiceExtra::filter($filter)->find(is_array($ids) ? $ids : []);

        foreach ($models as $model) {
            $model->galleries()->delete();
            $model->delete();
        }
    }
}
