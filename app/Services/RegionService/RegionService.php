<?php
declare(strict_types=1);

namespace App\Services\RegionService;

use App\Helpers\ResponseError;
use App\Models\Region;
use App\Services\CoreService;
use App\Traits\SetTranslations;
use Exception;
use Throwable;

final class RegionService extends CoreService
{
    use SetTranslations;

    protected function getModelClass(): string
    {
        return Region::class;
    }

    public function create(array $data): array
    {
        try {
            $model = $this->model()->create($data);
            $this->setTranslations($model, $data);

            return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $model];
        } catch (Exception $e) {
            $this->error($e);
            return ['status' => false, 'code' => ResponseError::ERROR_501, 'message' => ResponseError::ERROR_501];
        }
    }

    public function update(Region $model, $data): array
    {
        try {
            $model->update($data);
            $this->setTranslations($model, $data);

            return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $model];
        } catch (Exception $e) {
            $this->error($e);
            return ['status' => false, 'code' => ResponseError::ERROR_502, 'message' => ResponseError::ERROR_502];
        }
    }

    public function delete(?array $ids = []): array
    {
        foreach (Region::whereIn('id', is_array($ids) ? $ids : [])->get() as $model) {

            /** @var Region $model */
            $model->delete();
        }

        return ['status' => true, 'code' => ResponseError::ERROR_503];
    }

    public function changeActive(int $id): array
    {
        try {
            $model = Region::find($id);
            $model->update(['active' => !$model->active]);

            return [
                'status' => true,
                'code'   => ResponseError::NO_ERROR,
                'data'   => $model
            ];
        } catch (Throwable $e) {
            $this->error($e);
            return ['status' => false, 'code' => ResponseError::ERROR_502, 'message' => ResponseError::ERROR_502];
        }
    }

}
