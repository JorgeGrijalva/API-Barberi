<?php
declare(strict_types=1);

namespace App\Services\BannerService;

use App\Helpers\ResponseError;
use App\Models\Banner;
use App\Services\CoreService;
use App\Traits\SetTranslations;
use DB;
use Throwable;

class BannerService extends CoreService
{
    use SetTranslations;

    /**
     * @return string
     */
    protected function getModelClass(): string
    {
        return Banner::class;
    }

    /**
     * @param array $data
     * @return array
     */
    public function create(array $data): array
    {
        try {
            $banner = DB::transaction(function () use ($data) {

                /** @var Banner $banner */
                $banner = $this->model()->create($data);

                $banner->products()->sync(data_get($data, 'products', []));

                $this->setTranslations($banner, $data);

                if (data_get($data, 'images.0')) {
                    $banner->uploads(data_get($data, 'images'));
                    $banner->update(['img' => data_get($data, 'previews.0') ?? data_get($data, 'images.0')]);
                }

                return $banner;
            });

            return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $banner];

        } catch (Throwable $e) {
            $this->error($e);
        }

        return ['status' => false, 'code' => ResponseError::ERROR_501, 'message' => ResponseError::ERROR_501];
    }

    public function update(Banner $banner, array $data): array
    {
        try {
            DB::transaction(function () use ($banner, $data) {

                $banner->update($data);

                $banner->products()->sync(data_get($data, 'products', []));

                $this->setTranslations($banner, $data);

                if (data_get($data, 'images.0')) {
                    $banner->galleries()->delete();
                    $banner->uploads(data_get($data, 'images'));
                    $banner->update(['img' => data_get($data, 'previews.0') ?? data_get($data, 'images.0')]);
                }

            });

            return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $banner];

        } catch (Throwable $e) {
            $this->error($e);
            return ['status' => false, 'code' => ResponseError::ERROR_400, 'message' => ResponseError::ERROR_400];
        }
    }

    public function destroy(?array $ids = [], ?int $shopId = null) {

        /** @var Banner $banners */
        $banners = $this->model();

        foreach ($banners->whereIn('id', is_array($ids) ? $ids : [])->get() as $banner) {

            /** @var Banner $banner */

            if ($banner->type == Banner::BANNER) {

                $sync = $banner->products->pluck('id')->toArray();

                if (!empty($shopId)) {
                    $sync = $banner->products->where('shop_id', '!=', $shopId)->pluck('id')->toArray();
                }

                $banner->products()->sync($sync);

                if (empty($shopId) || count($sync)) {
                    $banner->galleries()->delete();
                    $banner->delete();
                }

                continue;
            } else if ($banner->type === Banner::LOOK && $banner->shop_id == $shopId) {
                $banner->delete();
                continue;
            }

            if (empty($shopId)) {
                $banner->delete();
            }

        }

    }

    public function setActiveBanner(int $id): array
    {
        $banner = $this->model()->find($id);

        if (empty($banner)) {
            return ['status' => false, 'code' => ResponseError::ERROR_400, 'message' => ResponseError::ERROR_400];
        }

        /** @var Banner $banner */
        $banner->update(['active' => !$banner->active]);

        return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $banner];
    }
}
