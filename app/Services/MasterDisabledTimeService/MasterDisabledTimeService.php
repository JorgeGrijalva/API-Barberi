<?php
declare(strict_types=1);

namespace App\Services\MasterDisabledTimeService;

use App\Helpers\ResponseError;
use App\Models\MasterDisabledTime;
use App\Services\CoreService;
use App\Traits\SetTranslations;
use Exception;
use Throwable;

class MasterDisabledTimeService extends CoreService
{
    use SetTranslations;

    protected function getModelClass(): string
    {
        return MasterDisabledTime::class;
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
                'status'  => true,
                'message' => ResponseError::NO_ERROR,
            ];

        } catch (Throwable $e) {

            $this->error($e);

            return ['status' => false, 'message' => ResponseError::ERROR_501, 'code' => ResponseError::ERROR_501];
        }
    }

    public function update(MasterDisabledTime $masterDisabledTime, array $data): array
    {
        try {

            if ($masterDisabledTime->master_id !== data_get($data, 'master_id')) {
                throw new Exception(__('errors.' . ResponseError::ERROR_404, locale: $this->language));
            }

            $masterDisabledTime->update($data);

            $this->setTranslations($masterDisabledTime, $data);

            return [
                'status'  => true,
                'message' => ResponseError::NO_ERROR,
            ];

        } catch (Throwable $e) {

            $this->error($e);

            return ['status' => false, 'code' => ResponseError::ERROR_501, 'message' => $e->getMessage()];
        }
    }

    public function delete(?array $ids = [], array $filter = []) {

        $models = MasterDisabledTime::filter($filter)->find(is_array($ids) ? $ids : []);

        foreach ($models as $model) {
            $model->delete();
        }

    }
}
