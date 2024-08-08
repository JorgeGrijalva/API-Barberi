<?php
declare(strict_types=1);

namespace App\Services\AreaService;

use App\Helpers\ResponseError;
use App\Models\Area;
use App\Models\City;
use App\Services\CoreService;
use App\Traits\SetTranslations;
use Exception;
use Throwable;

final class AreaService extends CoreService
{
    use SetTranslations;

    protected function getModelClass(): string
    {
        return Area::class;
    }

    public function create(array $data): array
    {
        try {
            $city = City::find(data_get($data, 'city_id'));

            $data['country_id'] = $city?->country_id;
            $data['region_id']  = $city?->region_id;

            $model = $this->model()->create($data);

            $this->setTranslations($model, $data);

            return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $model];
        } catch (Exception $e) {
            $this->error($e);
            return ['status' => false, 'code' => ResponseError::ERROR_501, 'message' => $e->getMessage()];
        }
    }

    public function update(Area $model, $data): array
    {
        try {
            $city = City::find(data_get($data, 'city_id'));

            $data['country_id'] = $city?->country_id;
            $data['region_id']  = $city?->region_id;

            $model->update($data);

            $this->setTranslations($model, $data);

            return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $model];
        } catch (Exception $e) {
            $this->error($e);
            return ['status' => false, 'code' => ResponseError::ERROR_502, 'message' => $e->getMessage()];
        }
    }

    public function delete(?array $ids = []): array
    {
        foreach (Area::whereIn('id', is_array($ids) ? $ids : [])->get() as $model) {

            /** @var Area $model */
            $model->delete();
        }

        return ['status' => true, 'code' => ResponseError::NO_ERROR];
    }

    public function changeActive(int $id): array
    {
        try {
            $model = Area::find($id);
            $model->update(['active' => !$model->active]);

            return [
                'status' => true,
                'code'   => ResponseError::NO_ERROR,
                'data'   => $model
            ];
        } catch (Throwable $e) {
            $this->error($e);
            return ['status' => false, 'code' => ResponseError::ERROR_502, 'message' => $e->getMessage()];
        }
    }

}
