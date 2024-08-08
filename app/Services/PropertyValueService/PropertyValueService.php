<?php
declare(strict_types=1);

namespace App\Services\PropertyValueService;

use App\Helpers\ResponseError;
use App\Models\ProductProperty;
use App\Models\PropertyGroup;
use App\Models\PropertyValue;
use App\Repositories\PropertyRepository\PropertyGroupRepository;
use App\Services\CoreService;
use App\Traits\SetTranslations;
use Throwable;

class PropertyValueService extends CoreService
{
    use SetTranslations;

    /**
     * @return string
     */
    protected function getModelClass(): string
    {
        return PropertyValue::class;
    }

    public function create(array $data): array
    {
        try {

            $group = (new PropertyGroupRepository)->show((int)data_get($data, 'property_group_id'));

            if (empty($group)) {
                return [
                    'status' => false,
                    'code'   => ResponseError::ERROR_404
                ];
            }

            /** @var PropertyValue $model */
            /** @var PropertyGroup $group */

            $model = $group->propertyValues()->create($data);

            $images = data_get($data, 'images', []);

            if (is_array($images)) {
                $model->galleries()->delete();
                $model->uploads($images ?: []);
            }

            return [
                'status'    => true,
                'code'      => ResponseError::NO_ERROR,
                'data'      => $model,
            ];
        } catch (Throwable $e) {

            $this->error($e);

            return [
                'status'    => false,
                'code'      => ResponseError::ERROR_501,
                'message'   => $e->getMessage() . ' ' . $e->getLine(),
            ];
        }
    }

    public function update(int $id, array $data): array
    {
        try {
            $model = $this->model()->find($id);

            if (empty($model)) {
                return [
                    'status' => false,
                    'code'   => ResponseError::NO_ERROR
                ];
            }

            /** @var PropertyValue $model */
            $model->update($data);

            $images = data_get($data, 'images');

            if (is_array($images)) {
                $model->galleries()->delete();

                $model->uploads($images ?: []);
            }

            return [
                'status'    => true,
                'code'      => ResponseError::NO_ERROR,
                'data'      => $model->refresh(),
            ];
        } catch (Throwable $e) {

            $this->error($e);

            return [
                'status'    => false,
                'code'      => ResponseError::ERROR_501,
            ];
        }
    }

    public function delete(?array $ids): void
    {
        foreach ($this->model()->whereIn('id', is_array($ids) ? $ids : [])->get() as $model) {

            /** @var PropertyValue $model */

            ProductProperty::where('property_value_id', $model->id)->delete();

            $model->delete();

        }
    }

    public function changeActive(int $id): array
    {
        $model = PropertyValue::find($id);

        if (empty($model)) {
            return [
                'status' => false,
                'code' => ResponseError::ERROR_404
            ];
        }

        $model->update(['active' => !$model->active]);

        return [
            'status' => true,
            'code'   => ResponseError::NO_ERROR,
            'data'   => $model,
        ];
    }
}
