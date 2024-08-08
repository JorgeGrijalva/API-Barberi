<?php
declare(strict_types=1);

namespace App\Services\AdsPackageService;

use App\Helpers\ResponseError;
use App\Models\AdsPackage;
use App\Models\Language;
use App\Services\CoreService;
use App\Traits\SetTranslations;
use Exception;
use Throwable;

final class AdsPackageService extends CoreService
{
    use SetTranslations;

    protected function getModelClass(): string
    {
        return AdsPackage::class;
    }

    public function create(array $data): array
    {
        try {
            $model = $this->model()->create($data);

            $this->setTranslations($model, $data);

            if (data_get($data, 'images.0')) {
                $model->uploads(data_get($data, 'images'));
                $model->update(['img' => data_get($data, 'images.0')]);
            }

            $locale = Language::where('default', 1)->first()?->locale;

            return [
                'status' => true,
                'code'   => ResponseError::NO_ERROR,
                'data'   => $model->loadMissing([
                    'translation' => fn($query) => $query
                        ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
                    'translations',
                ])
            ];
        } catch (Exception $e) {
            $this->error($e);
            return ['status' => false, 'code' => ResponseError::ERROR_501, 'message' => $e->getMessage()];
        }
    }

    public function update(AdsPackage $model, $data): array
    {
        try {
            $model->update($data);

            $this->setTranslations($model, $data);

            if (data_get($data, 'images.0')) {
                $model->galleries()->delete();
                $model->uploads(data_get($data, 'images'));
                $model->update(['img' => data_get($data, 'images.0')]);
            }

            $locale = Language::where('default', 1)->first()?->locale;

            return [
                'status' => true,
                'code'   => ResponseError::NO_ERROR,
                'data'   => $model->loadMissing([
                    'translation' => fn($query) => $query
                        ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
                    'translations',
                ])
            ];
        } catch (Exception $e) {
            $this->error($e);
            return ['status' => false, 'code' => ResponseError::ERROR_502, 'message' => $e->getMessage()];
        }
    }

    public function delete(?array $ids = []): array
    {
        foreach (AdsPackage::whereIn('id', is_array($ids) ? $ids : [])->get() as $model) {

            /** @var AdsPackage $model */
            $model->delete();
        }

        return ['status' => true, 'code' => ResponseError::NO_ERROR];
    }

    public function changeActive(int $id): array
    {
        try {
            $model = AdsPackage::find($id);
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
