<?php
declare(strict_types=1);

namespace App\Services\ServiceMasterService;

use DB;
use Exception;
use Throwable;
use App\Models\ServiceMaster;
use App\Services\CoreService;
use App\Helpers\ResponseError;
use App\Traits\SetTranslations;
use App\Services\ShopServices\ShopService;

class ServiceMasterService extends CoreService
{
    use SetTranslations;

    protected function getModelClass(): string
    {
        return ServiceMaster::class;
    }

    public function create(array $data): array
    {
        try {
            $model = DB::transaction(function () use ($data) {

                /** @var ServiceMaster $model */
                $pricing = data_get($data, 'pricing');

                unset($data['pricing']);

                $model = $this->model()->updateOrCreate([
                    'master_id'  => $data['master_id']  ?? null,
                    'service_id' => $data['service_id'] ?? null,
                    'shop_id'    => $data['shop_id']    ?? null,
                ], $data);

                $this->updatePrice($model, $pricing);

                return $model->fresh();
            });

            return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $model];
        } catch (Throwable $e) {
            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_400,
                'message' => $e->getMessage() . $e->getFile() . $e->getLine()
            ];
        }
    }

    /**
     * @param ServiceMaster $model
     * @param array $data
     * @return array
     */
    public function update(ServiceMaster $model, array $data): array
    {
        try {
            $model = DB::transaction(function () use ($model, $data) {

                /** @var ServiceMaster $model */
                $model = $this->model()->updateOrCreate([
                    'master_id'  => $model->master_id,
                    'service_id' => $data['service_id'] ?? null,
                    'shop_id'    => $data['shop_id']    ?? null,
                ], $data);

                $this->updatePrice($model, data_get($data, 'pricing'));

                return $model->fresh();
            });

            return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $model];
        } catch (Throwable $e) {
            return ['status' => false, 'code' => ResponseError::ERROR_400, 'message' => $e->getMessage()];
        }
    }

    private function updatePrice(ServiceMaster $model, $pricing) {

        $min = $model->price;
        $max = $model->price;

        if (!empty($pricing)) {

            $model->pricing()->delete(); // alohida api bo`masa if tepasiga

            foreach ($pricing as $value) {

                $price = $model->pricing()->create($value);

                $this->setTranslations($price, $value);
            }

            $min = (float)collect($pricing)->min('price');
            $max = (float)collect($pricing)->max('price');

            if ($min > $model->price) {
                $min = $model->price;
            }

            if ($max < $model->price) {
                $max = $model->price;
            }

        }

        (new ShopService)->updateShopPrices($model, $min, $max);
    }
    /**
     * @param array $ids
     * @return array
     */
    public function delete(array $ids = []): array
    {
        try {
            /** @var ServiceMaster[] $services */
            $services = ServiceMaster::with(['shop'])
                ->whereIn('id', data_get($ids, 'ids', []))
                ->when(data_get($ids, 'master_id'), fn($q, $masterId) => $q->where('master_id', $masterId))
                ->when(data_get($ids, 'shop_id'),   fn($q, $shopId)   => $q->where('shop_id',   $shopId))
                ->get();

            foreach ($services as $service) {

                /** @var object $price */
                $price = DB::table('service_masters')
                    ->select([
                        DB::raw('min(price) as min_price'),
                        DB::raw('max(price) as max_price'),
                    ])
                    ->where('shop_id', $service->shop_id)
                    ->first();

                $service->shop->update([
                    'service_min_price' => $price?->min_price ?? 1,
                    'service_max_price' => $price?->max_price ?? 1,
                ]);

                $service->delete();
            }

            return ['status' => true, 'code' => ResponseError::NO_ERROR];
        } catch (Exception $e) {
            return ['status' => false, 'code' => ResponseError::ERROR_400, 'message' => $e->getMessage()];
        }
    }
}
