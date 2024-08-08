<?php
declare(strict_types=1);

namespace App\Exports;

use App\Models\Shop;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ShopExport extends BaseExport implements FromCollection, WithHeadings
{
    /**
     * @return Collection
     */
    public function collection(): Collection
    {
        $shops = Shop::orderBy('id')->get();

        return $shops->map(fn (Shop $shop) => $this->tableBody($shop));
    }

    /**
     * @return string[]
     */
    public function headings(): array
    {
        return [
            '#',                        //0
            'uuid',                     //1
            'User Id',                  //2
            'Tax',                      //3
            'Percentage',               //4
            'Location',                 //5
            'Phone',                    //6
            'Open',                     //7
            'Img Urls',                 //8
            'Min Amount',               //9
            'Status',                   //10
            'Status Note',              //11
            'Created At',               //12
            'Delivery Time',            //13
            'Type',                     //14
            'Verify',                   //15
            'Visibility',               //16

        ];
    }

    /**
     * @param Shop $shop
     * @return array
     */
    private function tableBody(Shop $shop): array
    {

        $from = data_get($shop->delivery_time, 'from', '');
        $to   = data_get($shop->delivery_time, 'to', '');
        $type = data_get($shop->delivery_time, 'type', '');

        return [
            'id'                => $shop->id, //0
            'uuid'              => $shop->uuid, //1
            'user_id'           => $shop->user_id, //2
            'tax'               => $shop->tax, //3
            'percentage'        => $shop->percentage, //4
            'location'          => implode(',', $shop->lat_long), //5
            'phone'             => $shop->phone, //6
            'open'              => $shop->open, //7
            'img_urls'          => $this->imageUrl($shop->galleries), //8
            'min_amount'        => $shop->min_amount, //9
            'status'            => $shop->status, //10
            'status_note'       => $shop->status_note, //11
            'created_at'        => $shop->created_at ?? date('Y-m-d H:i:s'),//12
            'delivery_time'     => "from: $from, to: $to, type: $type", //13
            'type'              => data_get(Shop::TYPES, $shop->type, 'shop'), //14
            'verify'            => $shop->verify, //15
            'visibility'        => $shop->visibility, //16
        ];
    }
}
