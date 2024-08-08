<?php
declare(strict_types=1);

namespace App\Services\DigitalFileService;

use App\Helpers\ResponseError;
use App\Models\DigitalFile;
use App\Models\Settings;
use App\Models\UserDigitalFile;
use App\Services\CoreService;
use Exception;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class DigitalFileService extends CoreService
{
    protected function getModelClass(): string
    {
        return DigitalFile::class;
    }

    public function create(array $data): array
    {
        try {
            $isAws = Settings::where('key', 'aws')->first();

            $options = [];

            if (data_get($isAws, 'value')) {
                $options = ['disk' => 's3'];
            }

            $path = Storage::disk('private')->put('files', data_get($data, 'file'), $options);

            $data['path'] = config('filesystems.private.url') . "/$path";

            $model = $this->model()->updateOrCreate([
                'product_id' => data_get($data, 'product_id'),
            ], $data);

            return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $model];
        } catch (Exception $e) {
            $this->error($e);
            return ['status' => false, 'code' => ResponseError::ERROR_501, 'message' => $e->getMessage()];
        }
    }

    public function update(DigitalFile $model, $data): array
    {
        try {

            if (data_get($data, 'file')) {

                $path = Storage::disk('private')->put('files', data_get($data, 'file'));
                $data['path'] = $path;

                Storage::disk('private')->delete("private/$model->path");
            }

            $model->update($data);

            return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $model];
        } catch (Exception $e) {
            $this->error($e);
            return ['status' => false, 'code' => ResponseError::ERROR_502, 'message' => $e->getMessage()];
        }
    }

    public function delete(?array $ids = [], ?int $shopId = null): array
    {
        $models = DigitalFile::when($shopId, function ($query, $shopId) {
                $query->whereHas('product', fn($q) => $q->where('shop_id', $shopId));
            })
            ->whereIn('id', is_array($ids) ? $ids : [])
            ->get();

        foreach ($models as $model) {

            /** @var DigitalFile $model */
            Storage::disk('private')->delete("private/$model->path");

            $model->delete();
        }

        return ['status' => true, 'code' => ResponseError::NO_ERROR];
    }

    public function changeActive(int $id, ?int $shopId = null): array
    {
        try {
            /** @var DigitalFile $model */
            $model = DigitalFile::with(['product'])->find($id);

            if ($model->product?->shop_id !== $shopId) {
                return [
                    'status' => false,
                    'code' => ResponseError::ERROR_404,
                    'message' => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
                ];
            }

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

    public function getDigitalFile(?UserDigitalFile $model): array
    {
        if (!$model || !$model->digitalFile?->path) {
            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_404,
                'message' => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
            ];
        }

        if (!$model->active) {
            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_218,
                'message' => __('errors.' . ResponseError::ERROR_218, locale: $this->language),
            ];
        }

        if (!$model->downloaded) {
            $model->update([
                'downloaded' => true
            ]);
        }

        return [
            'status' => true,
            'data'   => "private/{$model->digitalFile->path}"
        ];
    }

}
