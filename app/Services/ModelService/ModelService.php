<?php
declare(strict_types=1);

namespace App\Services\ModelService;

use App\Helpers\ResponseError;
use App\Models\Service;
use App\Models\ServiceExtra;
use App\Models\ServiceFaq;
use App\Services\CoreService;
use App\Services\ShopServices\ShopService;
use App\Traits\SetTranslations;
use DB;
use Exception;
use Throwable;

class ModelService extends CoreService
{
    use SetTranslations;

    protected function getModelClass(): string
    {
        return Service::class;
    }

    public function create(array $data): array
    {
        try {
            $model = DB::transaction(function () use ($data) {

                /** @var Service $model */
                $model = $this->model()->create($data);

                (new ShopService)->updateShopPrices($model);

                $this->setTranslations($model, $data);

                if ($model && data_get($data, 'images.0')) {
                    $model->update(['img' => data_get($data, 'previews.0') ?? data_get($data, 'images.0')]);
                    $model->uploads(data_get($data, 'images'));
                }

                return $model;
            });

            return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $model];
        } catch (Throwable $e) {
            return ['status' => false, 'code' => ResponseError::ERROR_400, 'message' => $e->getMessage()];
        }
    }

    /**
     * @param int $id
     * @param array $data
     * @return array
     */
    public function extrasUpdate(int $id, array $data): array
    {
        try {
            $model = DB::transaction(function () use ($id, $data) {

                $model = Service::find($id);

                if (empty($model)) {
                    throw new Exception(__('errors.' . ResponseError::ERROR_404, locale: $this->language));
                }

                $model->serviceExtras()->delete();

                foreach ($data['extras'] as $extra) {

                    /** @var ServiceExtra $serviceExtra */
                    $extra['shop_id'] = $model->shop_id;
                    $serviceExtra = $model->serviceExtras()->create($extra);
                    $this->setTranslations($serviceExtra, $extra);

                    if (isset($data['img'])) {
                        $serviceExtra->uploads([$data['img']]);
                    }

                }

                return $model;
            });

            return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $model->fresh(['serviceExtras'])];
        } catch (Throwable $e) {
            return ['status' => false, 'code' => ResponseError::ERROR_400, 'message' => $e->getMessage()];
        }
    }

    /**
     * @param int $id
     * @param array $data
     * @return array
     */
    public function faqsUpdate(int $id, array $data): array
    {
        try {
            $model = DB::transaction(function () use ($id, $data) {

                $model = Service::find($id);

                if (empty($model)) {
                    throw new Exception(__('errors.' . ResponseError::ERROR_404, locale: $this->language));
                }

                $model->serviceFaqs()->delete();

                foreach ($data['faqs'] as $faq) {

                    $serviceFaq = $model->serviceFaqs()->create($faq);

                    /** @var ServiceFaq $serviceFaq */
                    if (count($faq['question'] ?? []) === 0) {
                        continue;
                    }

                    $serviceFaq->translations()->delete();

                    foreach ($faq['question'] as $index => $value) {

                        $serviceFaq->translations()->create([
                            'locale'   => $index,
                            'question' => $value,
                            'answer'   => $faq['answer'][$index] ?? '',
                        ]);

                    }

                }

                return $model;
            });

            return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $model->fresh(['serviceFaqs'])];
        } catch (Throwable $e) {
            return ['status' => false, 'code' => ResponseError::ERROR_400, 'message' => $e->getMessage()];
        }
    }

    /**
     * @param Service $service
     * @param array $data
     * @return array
     */
    public function update(Service $service, array $data): array
    {
        try {
            $service = DB::transaction(function () use ($service, $data) {

                $service->update($data);

                (new ShopService)->updateShopPrices($service);

                $this->setTranslations($service, $data);

                if (data_get($data, 'images.0')) {
                    $service->update(['img' => data_get($data, 'previews.0') ?? data_get($data, 'images.0')]);
                    $service->uploads(data_get($data, 'images'));
                }

                return $service;
            });

            return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $service];
        }
        catch (Throwable $e) {
            return ['status' => false, 'code' => ResponseError::ERROR_400, 'message' => $e->getMessage()];
        }
    }

    /**
     * @param array $ids
     * @return array
     */
    public function delete(array $ids = []): array
    {
        try {
            $services = Service::whereIn('id', data_get($ids, 'ids', []))
                ->when(data_get($ids, 'shop_id'),   fn($q, $shopId) => $q->where('shop_id', $shopId))
                ->get();

            foreach ($services as $service) {
                /** @var Service $service */
                $service->galleries()->delete();
                $service->delete();
            }

            return ['status' => true, 'code' => ResponseError::NO_ERROR];
        } catch (Exception $e) {
            return ['status' => false, 'code' => ResponseError::ERROR_400, 'message' => $e->getMessage()];
        }
    }

}
