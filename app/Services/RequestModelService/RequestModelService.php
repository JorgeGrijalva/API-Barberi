<?php
declare(strict_types=1);

namespace App\Services\RequestModelService;

use App\Helpers\ResponseError;
use App\Models\Category;
use App\Models\Product;
use App\Models\RequestModel;
use App\Models\User;
use App\Services\CoreService;
use App\Services\ProductService\ProductAdditionalService;
use App\Services\UserServices\UserService;
use App\Traits\SetTranslations;
use DB;
use Exception;
use Throwable;

final class RequestModelService extends CoreService
{
    use SetTranslations;

    protected function getModelClass(): string
    {
        return RequestModel::class;
    }

    public function create(array $data): array
    {
        try {
            $type = data_get($data, 'type',RequestModel::CATEGORY);

            $data['status'] 	= RequestModel::STATUS_PENDING;
            $data['model_type']	= data_get(RequestModel::TYPES, $type);
            $data['model_id'] 	= data_get($data, 'id');

            /** @var RequestModel $model */
            $model = $this->model()->updateOrCreate([
                'model_id' 	 => data_get($data, 'id'),
                'model_type' => data_get(RequestModel::TYPES, $type),
            ], $data);

            return [
                'status' => true,
                'code'   => ResponseError::NO_ERROR,
                'data'   => $model->loadMissing(['model', 'createdBy'])
            ];
        } catch (Exception $e) {
            $this->error($e);
            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_501,
                'message' => $e->getMessage()
            ];
        }
    }

    public function update(RequestModel $requestModel, $data): array
    {
        try {
            $type = data_get($data, 'type',RequestModel::CATEGORY);

            $data['model_type'] = data_get(RequestModel::TYPES, $type);
            $data['model_id']   = data_get($data, 'id');

            $requestModel->update($data);

            return [
                'status' => true,
                'code'   => ResponseError::NO_ERROR,
                'data'   => $requestModel->loadMissing(['model', 'createdBy'])
            ];
        } catch (Exception $e) {
            $this->error($e);
            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_502,
                'message' => $e->getMessage()
            ];
        }
    }

    public function delete(?array $ids = [], ?int $createdBy = null): array
    {
        try {
            DB::table('request_models')
                ->when($createdBy, fn($q) => $q->where('created_by', $createdBy))
                ->whereIn('id', is_array($ids) ? $ids : [])
                ->delete();

            return ['status' => true, 'code' => ResponseError::NO_ERROR];
        } catch (Throwable $e) {
            return ['status' => false, 'code' => ResponseError::ERROR_503, 'message' => $e->getMessage()];
        }
    }

    public function changeStatus(int $id, array $data): array
    {
        try {

            DB::transaction(function () use ($data, $id) {

                /** @var RequestModel $requestModel */
                $requestModel = RequestModel::with(['model'])->find($id);

                $requestModel->update($data);

                if ($requestModel->status !== RequestModel::STATUS_APPROVED || empty($requestModel->model)) {
                    return;
                }

                $model = $requestModel->model;
                $data  = $requestModel->data;

                if ($requestModel->model_type === Category::class) {

                    $this->category($data, $model);

                } else if ($requestModel->model_type === Product::class) {

                    $this->product($data, $model);

                } else if ($requestModel->model_type === User::class) {

                    $this->user($data, $model);

                }

                $requestModel->delete();
            });

            return [
                'status' => true,
                'code'   => ResponseError::NO_ERROR,
            ];

        } catch (Throwable $e) {

            $this->error($e);

            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_502,
                'message' => $e->getMessage()
            ];

        }
    }

    /**
     * Approve product
     * @param array $data
     * @param Product $model
     * @return void
     * @throws Exception
     */
    private function product(array $data, Product $model): void
    {

        $data['extras'] = [];

        foreach (data_get($data, 'stocks', []) as $key => $stock) {
            $data['extras'][$key] = $stock;
            $data['extras'][$key]['ids'] = collect(data_get($stock, 'ids'))->pluck('value')->toArray();
        }

        foreach (data_get($data, 'images', []) as $key => $image) {
            $data['images'][$key] = data_get($image, 'url');
        }

        (new ProductAdditionalService)->addInStock($model->uuid, $data);

        if (data_get($data, 'meta')) {
            $model->setMetaTags($data);
        }

        if (data_get($data, 'props.*')) {

            $model->properties()->delete();

            $properties = data_get($data, 'props');

            foreach (is_array($properties) ? $properties : [] as $property) {

                foreach (is_array($property) ? $property : [] as $index => $values) {

                    $locale = key($values);

                    $model->properties()->create([
                        'locale' => $locale,
                        'key'    => $values[$locale],
                        'value'  => data_get($properties, "value.$index.$locale", []),
                    ]);

                }

            }

        }

        $this->defModel($data, $model);
    }

    /**
     * Approve category
     * @param array $data
     * @param Category $model
     * @return void
     */
    private function category(array $data, Category $model): void
    {
        $type = data_get($data, 'type', 'main');

        $data['type'] = data_get(Category::TYPES, $type, 1);

        $this->defModel($data, $model);
    }

    /**
     * Approve category
     * @param array $data
     * @param User $model
     * @return void
     */
    private function user(array $data, User $model): void
    {
        if (data_get($data, 'role') === 'deliveryman') {

            $model->syncRoles(['deliveryman']);

            $model->deliveryManSetting()->updateOrCreate([
                'user_id' => $model->id
            ], [
                'region_id' 		=> data_get($data, 'region_id'),
                'country_id' 		=> data_get($data, 'country_id'),
                'city_id' 			=> data_get($data, 'city_id'),
                'area_id' 			=> data_get($data, 'area_id'),
                'type_of_technique' => data_get($data, 'type_of_technique'),
                'brand' 			=> data_get($data, 'brand'),
                'model' 			=> data_get($data, 'model'),
                'number' 			=> data_get($data, 'number'),
                'color' 			=> data_get($data, 'color'),
                'online' 			=> data_get($data, 'online'),
                'location' 			=> data_get($data, 'location'),
                'width' 			=> data_get($data, 'width'),
                'height' 			=> data_get($data, 'height'),
                'length' 			=> data_get($data, 'length'),
                'kg' 				=> data_get($data, 'kg'),
            ]);
        } elseif ($data['role'] === 'master') {

            $model->syncRoles('master');

            (new UserService)->createDefaultWorkingDays($model);

            $this->setTranslations($model, $data);

        }

        if (data_get($data, 'images.0')) {
            $model->galleries()->delete();
            $model->uploads(data_get($data, 'images'));
            $model->update(['img' => data_get($data, 'images.0')]);
        }

    }

    /**
     * For other models
     * @param array $data
     * @param $model
     * @return void
     */
    private function defModel(array $data, $model): void
    {
        $model->update($data);

        if (data_get($data, 'images.0')) {
            $model->galleries()->delete();
            $model->uploads(data_get($data, 'images'));
            $model->update(['img' => data_get($data, 'images.0')]);
        }

        $this->setTranslations($model, $data);
    }
}
