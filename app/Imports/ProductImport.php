<?php
declare(strict_types=1);

namespace App\Imports;

use App\Models\Language;
use App\Models\Product;
use DB;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Throwable;

class ProductImport extends BaseImport implements ToCollection, WithHeadingRow, WithBatchInserts
{
    use Importable;

    private ?int $shopId;
    private string $language;

    public function __construct(?int $shopId, string $language)
    {
        $this->shopId   = $shopId;
        $this->language = $language;
    }

    /**
     * @param Collection $collection
     * @return void
     * @throws Throwable
     */
    public function collection(Collection $collection): void
    {
        $language = Language::where('default', 1)->first();

        foreach ($collection as $row) {

            DB::transaction(function () use ($row, $language) {

                $data = [
                    'shop_id'     => $this->shopId ?? data_get($row,'shop_id'),
                    'category_id' => data_get($row, 'category_id'),
                    'brand_id'    => data_get($row, 'brand_id'),
                    'unit_id'     => data_get($row, 'unit_id'),
                    'keywords'    => data_get($row, 'keywords', ''),
                    'tax'         => data_get($row, 'tax', 0),
                    'active'      => data_get($row, 'active') === 'active' ? 1 : 0,
                    'qr_code'     => data_get($row, 'qr_code', ''),
                    'status'      => in_array(data_get($row, 'status'), Product::STATUSES) ? data_get($row, 'status') : Product::PENDING,
                    'min_qty'     => $row['min_qty']    ?? 0,
                    'max_qty'     => $row['max_qty']    ?? 1000000,
                    'digital'     => $row['digital']    ?? 0,
                    'age_limit'   => $row['age_limit']  ?? 0,
                    'visibility'  => $row['visibility'] ?? 1,
                    'min_price'   => $row['min_price']  ?? 0,
                    'max_price'   => $row['max_price']  ?? 0,
                ];

                try {
                    $product = Product::updateOrCreate($data);
                } catch (Throwable) {
                    return;
                }

                $this->downloadImages($product, data_get($row, 'img_urls', ''));

                // Translation
                if (!empty(data_get($row, 'product_title'))) {

                    $product->translation()->updateOrCreate([
                        'product_id' => $product->id,
                        'locale'     => $this->language ?? $language
                    ], [
                       'title'       => data_get($row, 'product_title', ''),
                       'description' => data_get($row, 'product_description', '')
                    ]);
                }

                // Stock
                if (!empty(data_get($row, 'price')) || !empty(data_get($row, 'quantity'))) {
                    $product->stocks()->updateOrCreate([
                        'product_id'  => $product->id
                    ], [
                        'price'       => data_get($row, 'price') > 0 ? data_get($row, 'price') : 0,
                        'quantity'    => data_get($row, 'quantity') > 0 ? data_get($row, 'quantity') : 0,
                        'sku'         => data_get($row, 'sku', ''),
                    ]);
                }
            });

        }
    }

    public function headingRow(): int
    {
        return 1;
    }

    public function batchSize(): int
    {
        return 200;
    }

    public function chunkSize(): int
    {
        return 200;
    }

}
