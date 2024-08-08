<?php
declare(strict_types=1);

namespace App\Services\ShopServices;

use App\Helpers\FileHelper;
use App\Helpers\ResponseError;
use App\Models\Invitation;
use App\Models\Language;
use App\Models\Order;
use App\Models\Service;
use App\Models\ServiceMaster;
use App\Models\Shop;
use App\Models\User;
use App\Services\CoreService;
use App\Traits\SetTranslations;
use DB;
use Exception;
use Throwable;

class ShopService extends CoreService
{
    use SetTranslations;

    protected function getModelClass(): string
    {
        return Shop::class;
    }

    /**
     * Create a new Shop model.
     * @param array $data
     * @return array
     */
    public function create(array $data): array
    {
        try {

            if (!isset($data['user_id'])) {
                throw new Exception(__('errors.' . ResponseError::ERROR_404, locale: $this->language));
            }

            $seller = User::with(['roles'])->find($data['user_id']);

            /** @var User $seller */
            if ($seller?->hasRole('admin')) {
                throw new Exception(__('errors.' . ResponseError::ERROR_207, locale: $this->language));
            }

            $shop = Shop::where('user_id', $data['user_id'])->first();

            if (!empty($shop)) {
                throw new Exception(__('errors.' . ResponseError::ERROR_206, locale: $this->language));
            }

            /** @var Shop $shop */
            $shop = DB::transaction(function () use($data) {

                /** @var Shop $shop */
                $shop = $this->model()->create($this->setShopParams($data));

                $this->setTranslations($shop, $data);

                if (data_get($data, 'images.0')) {
                    $shop->update([
                        'logo_img'       => data_get($data, 'images.0'),
                        'background_img' => data_get($data, 'images.1'),
                    ]);
                    $shop->uploads(data_get($data, 'images'));
                }

                if (data_get($data, 'tags.0')) {
                    $shop->tags()->sync(data_get($data, 'tags', []));
                }

                return $shop;
            });

            $locale = Language::where('default', 1)->first()?->locale;

            return [
                'status' => true,
                'code'   => ResponseError::NO_ERROR,
                'data'   => $shop->load([
                    'translation' => fn($query) => $query->where(function ($q) use ($locale) {
                        $q->where('locale', $this->language)->orWhere('locale', $locale);
                    }),
                    'subscription',
                    'seller.roles',
                    'tags.translation' => fn($query) => $query->where(function ($q) use ($locale) {
                        $q->where('locale', $this->language)->orWhere('locale', $locale);
                    }),
                    'seller' => fn($q) => $q->select('id', 'firstname', 'lastname', 'uuid'),
                ])
            ];
        } catch (Throwable $e) {
            $this->error($e);
            return [
                'status'    => false,
                'code'      => ResponseError::ERROR_501,
                'message'   => $e->getMessage() . $e->getFile() . $e->getLine(),
            ];
        }
    }

    /**
     * Update specified Shop model.
     * @param string $uuid
     * @param array $data
     * @return array
     */
    public function update(string $uuid, array $data): array
    {
        try {
            $shop = $this->model()
                ->with(['invitations'])
                ->when(data_get($data, 'user_id') && !request()->is('api/v1/dashboard/admin/*'), fn($q, $userId) => $q->where('user_id', $data['user_id']))
                ->where('uuid', $uuid)
                ->first();

            if (empty($shop)) {
                return ['status' => false, 'code' => ResponseError::ERROR_404];
            }

            /** @var Shop $parent */
            /** @var Shop $shop */
            $shop->update($this->setShopParams($data, $shop));

            if ($shop->delivery_type === Shop::DELIVERY_TYPE_IN_HOUSE) {
                Invitation::whereHas('user.roles', fn($q) => $q->where('name', 'deliveryman'))
                    ->where([
                        'shop_id' => $shop->id
                    ])
                    ->delete();
            }

            $this->setTranslations($shop, $data);

            if (data_get($data, 'images.0')) {
                $shop->galleries()->delete();
                $shop->update([
                    'logo_img'       => data_get($data, 'images.0'),
                    'background_img' => data_get($data, 'images.1'),
                ]);
                $shop->uploads(data_get($data, 'images'));
            }

            if (data_get($data, 'tags.0')) {
                $shop->tags()->sync(data_get($data, 'tags', []));
            }

            $locale = Language::where('default', 1)->first()?->locale;

            return [
                'status' => true,
                'code' => ResponseError::NO_ERROR,
                'data' => Shop::with([
                    'translation' => fn($query) => $query->where(function ($q) use ($locale) {
                        $q->where('locale', $this->language)->orWhere('locale', $locale);
                    }),
                    'subscription',
                    'seller.roles',
                    'tags.translation' => fn($query) => $query->where(function ($q) use ($locale) {
                        $q->where('locale', $this->language)->orWhere('locale', $locale);
                    }),
                    'seller' => fn($q) => $q->select('id', 'firstname', 'lastname', 'uuid'),
                    'workingDays',
                    'closedDates',
                ])->find($shop->id)
            ];
        } catch (Exception $e) {
            return [
                'status'  => false,
                'code'    => $e->getCode() ? 'ERROR_' . $e->getCode() : ResponseError::ERROR_400,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Delete Shop model.
     * @param array|null $ids
     * @return array
     */
    public function delete(?array $ids = []): array
    {

        foreach (Shop::with(['orders.pointHistories'])->whereIn('id', is_array($ids) ? $ids : [])->get() as $shop) {

            /** @var Shop $shop */

            FileHelper::deleteFile($shop->logo_img);
            FileHelper::deleteFile($shop->background_img);

            if (!$shop->seller?->hasRole('admin')) {
                $shop->seller?->syncRoles('user');
            }

            foreach ($shop->orders as $order) {
                /** @var Order $order */
                $order->pointHistories()->delete();
            }

            $shop->delete();

        }

        return ['status' => true, 'code' => ResponseError::NO_ERROR];
    }

    /**
     * Set params for Shop to update or create model.
     * @param array $data
     * @param Shop|null $shop
     * @return array
     */
    private function setShopParams(array $data, ?Shop $shop = null): array
    {
        $deliveryTime = $shop?->delivery_time ?? [];

        if (isset($data['delivery_time_from'])) {
            $deliveryTime['from'] = $data['delivery_time_from'];
        }

        if (isset($data['delivery_time_to'])) {
            $deliveryTime['to'] = $data['delivery_time_to'];
        }

        if (isset($data['delivery_time_type'])) {
            $deliveryTime['type'] = $data['delivery_time_type'];
        }

        if (isset($data['lat_long'])) {
            $data['latitude']  = @$data['lat_long']['latitude'];
            $data['longitude'] = @$data['lat_long']['longitude'];
            unset($data['lat_long']);
        }

        $data['delivery_time'] = $deliveryTime;
        $data['type']          = 1;

        return $data;
    }

    /**
     * @param string $uuid
     * @param array $data
     * @return array
     */
    public function imageDelete(string $uuid, array $data): array
    {
        $shop = Shop::firstWhere('uuid', $uuid);

        if (empty($shop)) {
            return [
                'status' => false,
                'code'   => ResponseError::ERROR_404,
                'data'   => $shop->refresh(),
            ];
        }

        $tag = data_get($data, 'tag');

        $shop->galleries()
            ->where('path', $tag === 'background' ? $shop->background_img : $shop->logo_img)
            ->delete();

        $shop->update([data_get($data, 'tag') . '_img' => null]);

        return [
            'status' => true,
            'code'   => ResponseError::NO_ERROR,
            'data'   => $shop->refresh(),
        ];
    }

    public function updateShopPrices(ServiceMaster|Service $model, float $newMinPrice = 0, float $newMaxPrice = 0): void
    {
        $shop = Shop::find($model->shop_id);

        if (empty($shop)) {
            return;
        }

        if ($newMinPrice <= 0) {
            $newMinPrice = $model->price;
        }

        if ($newMaxPrice <= 0) {
            $newMaxPrice = $model->price;
        }

        $minPrice = $shop->service_min_price;

        if ($minPrice > $newMinPrice) {
            $minPrice = $newMinPrice;
        }

        $maxPrice = $shop->service_max_price;

        if ($maxPrice < $newMaxPrice) {
            $maxPrice = $newMaxPrice;
        }

        if ($minPrice !== $shop->service_min_price || $maxPrice !== $shop->service_max_price) {
            $shop->update([
                'service_min_price' => $minPrice ?? 1,
                'service_max_price' => $maxPrice ?? 1,
            ]);
        }

    }

    /**
     * @param int|string $uuid
     * @return array
     */
    public function updateVerify(int|string $uuid): array
    {
        $shop = Shop::where('uuid', $uuid)->first();

        if (empty($shop) || $shop->uuid !== $uuid) {
            $shop = Shop::where('id', (int)$uuid)->first();
        }

        if (empty($shop)) {
            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_404,
                'message' => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
            ];
        }

        $shop->update(['verify' => !$shop->verify]);

        return [
            'status' => true,
            'data'   => $shop,
        ];
    }
}
