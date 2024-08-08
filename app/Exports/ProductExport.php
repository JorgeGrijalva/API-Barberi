<?php
declare(strict_types=1);

namespace App\Exports;

use App\Models\Language;
use App\Models\Product;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProductExport extends BaseExport implements FromCollection, WithHeadings
{
    protected array $filter;

    public function __construct(array $filter)
    {
        $this->filter = $filter;
    }

    public function collection(): Collection
    {
        $language = Language::where('default', 1)->first();

        $products = Product::filter($this->filter)
            ->with([
                'category.translation'  => fn($q) => $q
                    ->where(fn($q) => $q->where('locale', data_get($this->filter, 'language'))->orWhere('locale', $language)),

                'unit.translation'      => fn($q) => $q
                    ->where(fn($q) => $q->where('locale', data_get($this->filter, 'language'))->orWhere('locale', $language)),

                'translation'           => fn($q) => $q
                    ->where(fn($q) => $q->where('locale', data_get($this->filter, 'language'))->orWhere('locale', $language)),

                'brand:id,title',
            ])
            ->orderBy('id')
            ->get();

        return $products->map(fn(Product $product) => $this->tableBody($product));
    }

    public function headings(): array
    {
        return [
            '#',                    //0
            'Uu Id',                //1
            'Product Title',        //3
            'Product Description',  //4
            'Shop Id',              //5
            'Shop Name',            //6
            'Category Id',          //7
            'Category Title',       //8
            'Brand Id',             //9
            'Brand Title',          //10
            'Unit Id',              //11
            'Unit Title',           //12
            'Keywords',             //13
            'Tax',                  //14
            'Active',               //15
            'Qr Code',              //16
            'Status',               //17
            'Min Qty',              //18
            'Max Qty',              //19
            'Digital',              //20
            'Age Limit',            //21
            'Min Price',            //22
            'Max Price',            //23
            'Img Urls',             //24
            'Preview Urls',         //25
            'Created At',           //26
            'Visibility',           //27
        ];
    }

    private function tableBody(Product $product): array
    {
        return [
            'id'             => $product->id, //0
            'uuid'           => $product->uuid, //1
            'title'          => data_get($product->translation, 'title', ''), //3
            'description'    => data_get($product->translation, 'description', ''), //4
            'shop_id'        => $product->shop_id, //5
            'shop_title'     => data_get(optional($product->shop)->translation, 'title', ''), //6
            'category_id'    => $product->category_id ?? 0, //7
            'category_title' => data_get(optional($product->category)->translation, 'title', ''), //8
            'brand_id'       => $product->brand_id ?? 0, //9
            'brand_title'    => optional($product->brand)->title ?? 0, //10
            'unit_id'        => $product->unit_id ?? 0, //11
            'unit_title'     => data_get(optional($product->unit)->translation, 'title', ''), //12
            'keywords'       => $product->keywords ?? '', //13
            'tax'            => $product->tax ?? 0, //14
            'active'         => $product->active ? 'active' : 'inactive', //15
            'qr_code'        => $product->qr_code ?? '', //16
            'status'         => $product->status ?? Product::PENDING, //17
            'min_qty'        => $product->min_qty ?? 0, //18
            'max_qty'        => $product->max_qty ?? 0, //19
            'digital'        => $product->digital ?? 0, //20
            'age_limit'      => $product->age_limit ?? 0, //21
            'min_price'      => $product->min_price ?? 0, //22
            'max_price'      => $product->max_price ?? 0, //23
            'img_urls'       => $this->imageUrl($product->galleries, 'path') ?? '', //24
            'preview_urls'   => $this->imageUrl($product->galleries, 'preview') ?? '', //25
            'created_at'     => $product->created_at ?? date('Y-m-d H:i:s'), //26
            'visibility'     => $product->visibility, //27
        ];
    }
}
