<?php
declare(strict_types=1);

namespace App\Services\AdsPackageService;

use App\Helpers\ResponseError;
use App\Models\Payment;
use App\Models\ShopAdsPackage;
use App\Services\CoreService;
use App\Traits\PaymentRefund;
use DB;
use Exception;
use Throwable;

final class ShopAdsPackageService extends CoreService
{
    use PaymentRefund;

    protected function getModelClass(): string
    {
        return ShopAdsPackage::class;
    }

    public function create(array $data): array
    {
        try {
            $model = DB::transaction(function () use ($data) {

                /** @var ShopAdsPackage $model */
                $model = $this->model()->create($data);

//                $exists = ShopAdsPackage::where([
//                    ['expired_at', '>=', now()],
//                    ['shop_id', $model->shop_id],
//                    ['id', '!=', $model->id],
//                    ['status', ShopAdsPackage::APPROVED],
//                ])
//                    ->exists();

//                if ($exists) {
//                    throw new Exception(__('errors.'. ResponseError::ERROR_116, locale: $this->language));
//                }

                foreach (data_get($data, 'product_ids', []) as $value) {
                    $model->shopAdsProducts()->create([
                        'product_id' => (int)$value,
                    ]);
                }

            });

            return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $model];
        } catch (Throwable $e) {
            $this->error($e);
            return ['status' => false, 'code' => ResponseError::ERROR_501, 'message' => $e->getMessage()];
        }
    }

    /**
     * @param array|null $ids
     * @param int|null $shopId
     * @return array|int[]
     */
    public function delete(?array $ids = [], ?int $shopId = null): array
    {
        $shopAdsPackages = ShopAdsPackage::whereIn('id', is_array($ids) ? $ids : [])
            ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
            ->get();

        foreach ($shopAdsPackages as $shopAdsPackage) {
            $shopAdsPackage->delete();
        }

        return [
            'status' => true,
            'code' => ResponseError::NO_ERROR,
        ];
    }

    public function updateStatus(ShopAdsPackage $shopAdsPackage, array $data): array
    {
        try {
            $model = DB::transaction(function () use ($shopAdsPackage, $data) {

                $shopAdsPackage->update($data);
                $adsPackage = $shopAdsPackage->adsPackage;

                $shopAdsPackage->update([
                    'expired_at' => date('Y-m-d H:i:s', strtotime("+$adsPackage->time $adsPackage->time_type")),
                ]);

                if ($shopAdsPackage->status == ShopAdsPackage::CANCELED) {

                    $this->paymentRefund($shopAdsPackage);

                    $shopAdsPackage->update([
                        'expired_at' => date('Y-m-d H:i:s', strtotime("-1 day")),
                    ]);
                }

            });
            return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $model];
        } catch (Throwable $e) {
            $this->error($e);
            return ['status' => false, 'code' => ResponseError::ERROR_501, 'message' => $e->getMessage()];
        }
    }

}
