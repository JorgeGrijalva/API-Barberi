<?php
declare(strict_types=1);

namespace App\Services\PropertyGroupService;

use App\Helpers\ResponseError;
use App\Models\PropertyGroup;
use App\Services\CoreService;
use App\Traits\SetTranslations;
use Throwable;

class PropertyGroupService extends CoreService
{
    use SetTranslations;

    /**
     * @return string
     */
    protected function getModelClass(): string
    {
        return PropertyGroup::class;
    }


    public function create(array $data): array
    {
        try {
            /** @var PropertyGroup $model */
            $model = $this->model()->create($data);

            $this->setTranslations($model, $data);

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
            ];
        }
    }

    public function update(PropertyGroup $propertyGroup, array $data): array
    {
        try {
            $propertyGroup->update($data);
            $this->setTranslations($propertyGroup, $data);

            return [
                'status'    => true,
                'code'      => ResponseError::NO_ERROR,
                'data'      => $propertyGroup,
            ];
        } catch (Throwable $e) {

            $this->error($e);

            return [
                'status'    => false,
                'code'      => ResponseError::ERROR_501,
            ];
        }
    }

    public function delete(?array $ids, ?int $shopId = null): int
    {
        $hasValues = 0;

        $propertyGroups = $this->model()
            ->with([
                'propertyValues',
            ])
            ->whereIn('id', is_array($ids) ? $ids : [])
            ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
            ->get();

        foreach ($propertyGroups as $propertyGroup) {

            /** @var PropertyGroup $propertyGroup */

            if (count($propertyGroup->propertyValues) > 0) {
                $hasValues++;
                continue;
            }

            $propertyGroup->delete();
        }

        return $hasValues;
    }

    public function changeActive(int $id, ?int $shopId = null): array
    {
        $model = PropertyGroup::find($id);

        if (empty($model) || (!empty($shopId) && $model->shop_id !== $shopId)) {
            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_404,
                'message' => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
            ];
        }

        /** @var PropertyGroup $model */
        $model->update(['active' => !$model->active]);

        return [
            'status' => true,
            'code'   => ResponseError::NO_ERROR,
            'data'   => $model,
        ];
    }
}
