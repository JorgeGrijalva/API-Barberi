<?php
declare(strict_types=1);

namespace App\Services\FormOptionService;

use App\Helpers\ResponseError;
use App\Models\FormOption;
use App\Services\CoreService;
use App\Traits\SetTranslations;
use Exception;

class FormOptionService extends CoreService
{
    use SetTranslations;

    protected function getModelClass(): string
    {
        return FormOption::class;
    }

    public function create(array $data): array
    {
        try {
            $model = $this->model()->create($data);

            $this->setTranslations($model, $data);

            return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $model];
        } catch (Exception $e) {
            return ['status' => false, 'code' => ResponseError::ERROR_400, 'message' => $e->getMessage()];
        }
    }

    /**
     * @param FormOption $model
     * @param array $data
     * @return array
     */
    public function update(FormOption $model, array $data): array
    {
        try {
            $model->update($data);

            $this->setTranslations($model, $data);

            return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $model];
        }
        catch (Exception $e) {
            return ['status' => false, 'code' => ResponseError::ERROR_400, 'message' => $e->getMessage()];
        }
    }

    public function delete(array $ids, ?int $shopId = null, ?array $serviceMasterIds = null)
    {
        FormOption::whereIn('id', $ids)
            ->when($shopId,           fn($q) => $q->where('shop_id', $shopId))
            ->when($serviceMasterIds, fn($q) => $q->whereIn('service_master_id', $serviceMasterIds))
            ->delete();
    }

}
