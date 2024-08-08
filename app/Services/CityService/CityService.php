<?php
declare(strict_types=1);

namespace App\Services\CityService;

use App\Helpers\ResponseError;
use App\Models\City;
use App\Models\Country;
use App\Services\CoreService;
use App\Traits\SetTranslations;
use Exception;
use Throwable;

final class CityService extends CoreService
{
    use SetTranslations;

    protected function getModelClass(): string
    {
        return City::class;
    }

    public function create(array $data): array
    {
        try {
            $data['region_id'] = Country::find(data_get($data, 'country_id'))?->region_id;

            $model = $this->model()->create($data);

            $this->setTranslations($model, $data);

            return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $model];
        } catch (Exception $e) {
            $this->error($e);
            return ['status' => false, 'code' => ResponseError::ERROR_501, 'message' => $e->getMessage()];
        }
    }

    public function update(City $model, $data): array
    {
        try {
            $data['region_id'] = Country::find(data_get($data, 'country_id'))?->region_id;

            $model->update($data);

            $this->setTranslations($model, $data);

            return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $model];
        } catch (Exception $e) {
            $this->error($e);
            return ['status' => false, 'code' => ResponseError::ERROR_502, 'message' => ResponseError::ERROR_502 ];
        }
    }

    public function delete(?array $ids = []): array
    {
        foreach (City::whereIn('id', is_array($ids) ? $ids : [])->get() as $model) {

            /** @var City $model */
            $model->delete();
        }

        return ['status' => true, 'code' => ResponseError::NO_ERROR];
    }

    public function changeActive(int $id): array
    {
        try {
            $model = City::find($id);
            $model->update(['active' => !$model->active]);

            return [
                'status' => true,
                'code'   => ResponseError::NO_ERROR,
                'data'   => $model
            ];
        } catch (Throwable $e) {
            $this->error($e);
            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_502,
                'message' => $e->getMessage(),
            ];
        }
    }

}
