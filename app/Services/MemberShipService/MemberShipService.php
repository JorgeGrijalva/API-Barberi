<?php
declare(strict_types=1);

namespace App\Services\MemberShipService;

use App\Helpers\ResponseError;
use App\Models\MemberShip;
use App\Services\CoreService;
use App\Traits\SetTranslations;
use DB;
use Throwable;

class MemberShipService extends CoreService
{
    use SetTranslations;

    protected function getModelClass(): string
    {
        return MemberShip::class;
    }

    /**
     * @param array $data
     * @return array
     */
    public function create(array $data): array
    {
        try {
            $model = DB::transaction(function () use ($data) {

                /** @var MemberShip $model */
                $model = $this->model()->create($data);

                return $this->beforeSave($model, $data);
            });

            return [
                'status'  => true,
                'message' => ResponseError::NO_ERROR,
                'data'    => $model,
            ];

        } catch (Throwable $e) {

            $this->error($e);

            return ['status' => false, 'message' => $e->getMessage(), 'code' => ResponseError::ERROR_501];
        }
    }

    public function update(MemberShip $model, array $data): array
    {
        try {
            $model = DB::transaction(function () use ($model, $data) {

                $model->update($data);

                return $this->beforeSave($model, $data);
            });

            return [
                'status'  => true,
                'message' => ResponseError::NO_ERROR,
                'data'    => $model,
            ];

        } catch (Throwable $e) {

            $this->error($e);

            return ['status' => false, 'message' => $e->getMessage(), 'code' => ResponseError::ERROR_502];
        }
    }

    public function delete(?array $ids = [], array $filter = []): void
    {
        $models = MemberShip::filter($filter)->find(is_array($ids) ? $ids : []);

        foreach ($models as $model) {
            $model->delete();
        }

    }

    /**
     * @param MemberShip $model
     * @param array $data
     * @return MemberShip
     */
    public function beforeSave(MemberShip $model, array $data): MemberShip
    {
        $this->setTranslations($model, $data);

        if (data_get($data, 'images.0')) {
            $model->uploads(data_get($data, 'images'));
        }

        $services = $data['services'] ?? [];

        if (count($services) <= 0) {
            return $model;
        }

        $model->memberShipServices()->delete();
        $model->memberShipServices()->createMany($services);

        return $model;
    }

}
