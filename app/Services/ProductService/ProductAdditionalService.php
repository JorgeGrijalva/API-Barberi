<?php
declare(strict_types=1);

namespace App\Services\ProductService;

use App\Helpers\ResponseError;
use App\Models\ExtraValue;
use App\Models\Language;
use App\Models\Product;
use App\Models\PropertyValue;
use App\Models\Stock;
use App\Services\CoreService;
use DB;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class ProductAdditionalService extends CoreService
{
    /**
     * @return string
     */
    protected function getModelClass(): string
    {
        return Product::class;
    }

    /**
     * @return mixed
     */
    public function createOrUpdateProperties(string $uuid, array $data): array
    {
        $item = $this->model()->firstWhere('uuid', $uuid);

        if (empty($item)) {
            return ['status' => false, 'code' => ResponseError::ERROR_404];
        }

        try {
            /** @var Product $item */
            $item->properties()->delete();

            $properties = data_get($data, 'properties');

            foreach ($properties as $property) {

                $value = PropertyValue::find($property);

                $item->properties()->create([
                    'property_value_id' => $value->id,
                    'property_group_id' => $value->property_group_id,
                ]);

            }

            return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $item];
        } catch (Exception $e) {
            return ['status' => false, 'code' => ResponseError::ERROR_400, 'message' => $e->getMessage()];
        }

    }

    public function addInStock(string $uuid, array $data): array
    {
        $locale = Language::where('default', 1)->first()?->locale;
        $locale = request('lang', $locale);

        $product = $this->model()
            ->with(['stocks.stockExtras'])
            ->when(data_get($data, 'shop_id'), fn($q, $shopId) => $q->where('shop_id', $shopId))
            ->firstWhere('uuid', $uuid);

        if (empty($product)) {
            return ['status' => false, 'code' => ResponseError::ERROR_404];
        }

        try {
            /** @var Product $product */
            $product = DB::transaction(function () use ($product, $data, $locale) {

                $extras = data_get($data, 'extras', []);

                if (data_get($data, 'delete_ids')) {
                    $product->stocks()->whereIn('id', data_get($data, 'delete_ids'))->delete();
                }

                $deleteIds = [];

                foreach ($extras as $i => $item) {

                    $ids = data_get($item, 'ids');

                    // when trying to add duplicate stock
                    foreach ($extras as $k => $extra) {

                        $duplicateIds = data_get($extra, 'ids', []);

                        if (
                            $i !== $k && is_array($ids)
                            && is_array($duplicateIds)
                            && empty(array_diff($ids, $duplicateIds))
                        ) {

                            throw new Exception(__('errors.' . ResponseError::ERROR_119, locale: $locale), 119);

                        }

                    }

                    $stock = $this->stockUpdateOrCreate($item, $product);

                    if (empty($ids)) {
                        DB::table('stock_extras')->where('stock_id', $stock->id)->delete();
                    }

                    if (is_array($ids)) {

                        $stock->stockExtras()->delete();

                        $values = ExtraValue::find($ids);

                        foreach ($values as $value) {
                            $stock->stockExtras()->create([
                                'extra_group_id' => $value->extra_group_id,
                                'extra_value_id' => $value->id,
                            ]);
                        }

                    }

                    if (data_get($item, 'images.0')) {
                        $stock->galleries()->delete();
                        $stock->uploads(data_get($item, 'images'));
                    }

                    $wholeSales = collect(data_get($item, 'whole_sales'))
                        ->whereNotNull('min_quantity')
                        ->whereNotNull('max_quantity')
                        ->whereNotNull('price')
                        ->toArray();

                    if (count($wholeSales) > 0) {
                        $stock->wholeSalePrices()->delete();
                        $stock->wholeSalePrices()->createMany($wholeSales);
                    }

                    $deleteIds[] = $stock->id;
                }

                if (count($deleteIds) > 0) {
                    $product->fresh(['stocks'])->stocks()->whereNotIn('id', $deleteIds)->delete();
                }

                $product = $product->fresh([
                    'shop',
                    'stocks.galleries',
                    'stocks.stockExtras' => fn($q) => $q->with([
                        'value',
                        'group.translation' => fn($query) => $query->where(function ($q) use ($locale) {
                            $q->where('locale', $this->language)->orWhere('locale', $locale);
                        }),
                    ]),
                ]);

                $minPrice = max($product->stocks?->min('price'), 0);
                $maxPrice = max($product->stocks?->max('price'), 0);

                $product->update([
                    'min_price' => $minPrice,
                    'max_price' => $maxPrice
                ]);

                $product->shop->update([
                    'min_price' => $minPrice,
                    'max_price' => $maxPrice,
                ]);

                return $product;
            });

            return [
                'status'  => true,
                'code'    => ResponseError::NO_ERROR,
                'data'    => $product,
            ];
        } catch (Throwable $e) {
            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_400,
                'message' => $e->getMessage(),
            ];
        }

    }

    /**
     * @param array $item
     * @param Product $product
     * @return Stock|Model
     */
    private function stockUpdateOrCreate(array $item, Product $product): Stock|Model
    {

        if (data_get($item, 'stock_id')) {

            $stock = Stock::find(data_get($item, 'stock_id'));

            $stock->update([
                'product_id'    => $product->id,
                'price'         => data_get($item, 'price'),
                'quantity'      => data_get($item, 'quantity'),
                'sku'           => data_get($item, 'sku'),
            ]);

            return $stock;
        }

        return $product->stocks()->create([
            'product_id' => $product->id,
            'price'      => data_get($item, 'price'),
            'quantity'   => data_get($item, 'quantity'),
            'sku'        => data_get($item, 'sku'),
        ]);
    }

    /**
     * @param array $data
     * @return array
     */
    public function stockGalleryUpdate(array $data): array
    {
        $stocks = Stock::whereHas('product', function ($query) use ($data) {
            $query->when(data_get($data, 'shop_id'), function ($query) use ($data) {
                $query->where('shop_id', data_get($data, 'shop_id'));
            });
        })
            ->whereIn('id', data_get($data, 'data.*.id', []))
            ->get();

        if ($stocks->count() === 0) {
            return ['status' => false, 'code' => ResponseError::ERROR_404];
        }

        try {
            /** @var Stock $stock */
            $stocks = DB::transaction(function () use ($stocks, $data) {

                $images = collect(data_get($data, 'data'));

                foreach ($stocks as $stock) {

                    $stockImages = $images->where('id', $stock->id)->first();

                    if (!data_get($stockImages, 'images.0')) {
                        continue;
                    }

                    $stock->galleries()->delete();
                    $stock->uploads($stockImages['images']);

                }

                return $stocks->fresh('galleries');
            });

            return [
                'status' => true,
                'code'   => ResponseError::NO_ERROR,
                'data'   => $stocks,
            ];
        } catch (Throwable $e) {
            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_400,
                'message' => $e->getMessage(),
            ];
        }
    }

}
