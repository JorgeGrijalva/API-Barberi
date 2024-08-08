<?php
declare(strict_types=1);

namespace App\Exports;

use App\Models\Language;
use App\Models\Order;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class OrderExport extends BaseExport implements FromCollection, WithHeadings
{
    protected array $filter;

    public function __construct(array $filter)
    {
        $this->filter = $filter;
    }

    public function collection(): Collection
    {
        $language = Language::where('default', 1)->first();

        $orders = Order::filter($this->filter)
            ->with([
                'user:id,firstname',
                'orderDetails',
                'shop',
                'shop.translation'  => fn($q) => $q
                    ->select('locale', 'title', 'shop_id')
                    ->where(fn($q) => $q->where('locale', data_get($this->filter, 'language'))->orWhere('locale', $language)),

                'deliveryman:id,firstname',
            ])
            ->orderBy('id')
            ->get();

        return $orders->map(fn(Order $order) => $this->tableBody($order));
    }

    public function headings(): array
    {
        return [
            '#',                    //0
            'User Id',              //1
            'Username',             //2
            'Total Price',          //3
            'Currency Id',          //4
            'Currency Title',       //5
            'Rate',                 //6
            'Note',                 //7
            'Shop Id',              //8
            'Shop Title',           //9
            'Tax',                  //10
            'Status',               //12
            'Delivery Fee',         //13
            'Deliveryman',          //14
            'Deliveryman Firstname',//15
            'Delivery Date',        //16
            'Delivery Time',        //17
            'Total Discount',       //18
            'Location',             //19
            'Address',              //20
            'Delivery Type',        //21
            'Phone',                //22
            'Created At',           //23
        ];
    }

    private function tableBody(Order $order): array
    {
        $currencyTitle  = data_get($order->currency, 'title');
        $currencySymbol = data_get($order->currency, 'symbol');

        $shopId    = $order->orderDetails->pluck('shop_id');
        $shopTitle = $order->orderDetails->pluck('shop.translation.title');

        return [
           'id'                     => $order->id, //0
           'user_id'                => $order->user_id, //1
           'username'               => $order->username ?? optional($order->user)->firstname, //2
           'total_price'            => $order->total_price, //3
           'currency_id'            => $order->currency_id, //4
           'currency_title'         => "$currencyTitle($currencySymbol)", //5
           'rate'                   => $order->rate, //6
           'note'                   => $order->note, //7
           'shop_id'                => $shopId->implode(', '), //8
           'shop_title'             => $shopTitle->implode(', '), //9
           'tax'                    => $order->total_tax, //10
           'status'                 => $order->status, //12
           'delivery_fee'           => $order->delivery_fee, //13
           'deliveryman'            => $order->deliveryman_id, //14
           'deliveryman_firstname'  => $order->deliveryman?->firstname, //15
           'delivery_date'          => $order->delivery_date, //16
           'total_discount'         => $order->total_discount, //18
           'location'               => $order->location ? implode(',', $order->location) : $order->location, //19
           'address'                => $order->address, //20
           'delivery_type'          => $order->delivery_type, //21
           'phone'                  => $order->phone, //22
           'created_at'             => $order->created_at ?? date('Y-m-d H:i:s'), //23
        ];
    }
}
