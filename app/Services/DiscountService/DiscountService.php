<?php
declare(strict_types=1);

namespace App\Services\DiscountService;

use App\Helpers\ResponseError;
use App\Models\Discount;
use App\Models\Stock;
use App\Services\CoreService;
use DB;
use Exception;
use Throwable;

class DiscountService extends CoreService
{
    protected function getModelClass(): string
    {
        return Discount::class;
    }

    public function create(array $data): array
    {
        try {
            /** @var Discount $discount */
            $discount = $this->model()->create($data);

            $stocks = collect(data_get($data, 'stocks'));

            if ($stocks->count() > 0) {

                $stocks = Stock::find($stocks);

                foreach ($stocks as $stock) {
                    $stock->update([
                        'discount_id'         => $discount->id,
                        'discount_expired_at' => $discount->end
                    ]);
                }

            }

            if (data_get($data, 'images.0')) {

                $discount->uploads(data_get($data, 'images'));
                $discount->update([
                    'img' => data_get($data, 'images.0')
                ]);

            }

            return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $discount];
        } catch (Exception $e) {
            $this->error($e);
            return ['status' => false, 'code' => ResponseError::ERROR_501, 'message' => $e->getMessage()];
        }
    }

    /**
     * @param Discount $discount
     * @param array $data
     * @return array
     */
    public function update(Discount $discount, array $data): array
    {
        try {
            $discount->update($data);

            $stocks = collect(data_get($data, 'stocks'));

            $discount
                ->loadMissing([
                    'stocks' => fn($q) => $q->whereNotIn('id', $stocks)
                ])
                ->stocks()
                ->update([
                    'discount_id'         => null,
                    'discount_expired_at' => null
                ]);

            if ($stocks->count() > 0) {

                $stocks = Stock::find($stocks);

                foreach ($stocks as $stock) {
                    $stock->update([
                        'discount_id'         => $discount->id,
                        'discount_expired_at' => $discount->end
                    ]);
                }

            }

            if (data_get($data, 'images.0')) {
                $discount->galleries()->delete();
                $discount->uploads(data_get($data, 'images'));
                $discount->update([
                    'img' => data_get($data, 'images.0')
                ]);

            }

            return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $discount];
        } catch (Exception $e) {
            $this->error($e);
            return ['status' => false, 'code' => ResponseError::ERROR_501];
        }
    }

    public function delete(array $ids, ?int $shopId = null): array
    {
        try {
            DB::transaction(function () use ($ids, $shopId) {

                $discounts = Discount::whereIn('id', $ids)
                    ->where('shop_id', $shopId)
                    ->get();

                foreach ($discounts as $discount) {

                    /** @var Discount $discount */
                    $discount->galleries()->delete();
                    $discount->stocks()->update([
                        'discount_id'         => $discount->id,
                        'discount_expired_at' => $discount->end
                    ]);

                    $discount->delete();
                }
            });

            return ['status' => true, 'code' => ResponseError::NO_ERROR];

        } catch (Throwable $e) {
            return ['status' => false, 'code' => $e->getCode(), 'message'=>  $e->getMessage()];
        }
    }
}
