<?php
declare(strict_types=1);

namespace App\Services\LanguageServices;

use App\Helpers\FileHelper;
use App\Helpers\ResponseError;
use App\Models\Language;
use App\Services\CoreService;
use DB;
use Exception;
use Illuminate\Support\Facades\Cache;
use Psr\SimpleCache\InvalidArgumentException;
use Throwable;

class LanguageService extends CoreService
{
    /**
     * @return string
     */
    protected function getModelClass(): string
    {
        return Language::class;
    }

    public function create(array $data): array
    {
        try {
            /** @var Language $language */
            $language = $this->model();

            $language = $language->updateOrCreate([
                'locale' => data_get($data, 'locale'),
            ], $data);

            $this->setDefault($language, data_get($data, 'default'));

            if (data_get($data, 'images.0')) {
                $language->galleries()->delete();
                $language->update(['img' => data_get($data, 'images.0')]);
                $language->uploads(data_get($data, 'images'));
            }

            return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $language];
        } catch (Exception $e) {
            return ['status' => false, 'code' => ResponseError::ERROR_400, 'message' => $e->getMessage()];
        }
    }

    /**
     * @param Language $language
     * @param array $data
     * @return array
     */
    public function update(Language $language, array $data): array
    {
        try {
            $language->update($data);

            $default = $language->default ?: data_get($data, 'default');

            $this->setDefault($language, $default);

            if (data_get($data, 'images.0')) {
                $language->galleries()->delete();
                $language->update(['img' => data_get($data, 'images.0')]);
                $language->uploads(data_get($data, 'images'));
            }

            return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $language];
        } catch (Throwable $e) {
            $this->error($e);
            return ['status' => false, 'code' => ResponseError::ERROR_400];
        }
    }

    /**
     * @param array|null $ids
     * @return array
     */
    public function delete(?array $ids = []): array
    {
        foreach (Language::whereIn('id', is_array($ids) ? $ids : [])->get() as $language) {

            /** @var Language $language */
            if ($language->default || Language::count() === 1) {
                continue;
            }

            FileHelper::deleteFile("images/languages/$language->img");

            $language->delete();
        }

        try {
            Cache::delete('languages-list');
        } catch (InvalidArgumentException) {
        }

        return ['status' => true, 'code' => ResponseError::NO_ERROR];
    }

    public function setLanguageDefault(int $id = null, int $default = null): array
    {
        $item = $this->model()->find($id);

        if (!$item) {
            return ['status' => false, 'code' => ResponseError::ERROR_404];
        }

        /** @var Language $item */
        return $this->setDefault($item, $default);
    }

    /**
     * Set Default status of Model
     * @param Language $language
     * @param int|bool|string $default
     * @return array
     */
    public function setDefault(Language $language, int|bool|string|null $default): array
    {
        if ($default) {
            DB::table('languages')
                ->where('default', 1)
                ->update([
                    'default' => 0,
                ]);
        }

        $language->default = $default ?: 1;
        $language->save();

        return ['status' => true, 'code' => ResponseError::NO_ERROR];
    }

}
