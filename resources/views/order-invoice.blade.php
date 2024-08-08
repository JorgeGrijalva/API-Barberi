<!doctype html>
<html lang="en">
<?php
/** @var App\Models\Order $order */
/** @var App\Models\OrderDetail $detail */

/** @var string $logo */

use App\Helpers\ResponseError;

$userName  = "{$order->user?->firstname} {$order->user?->lastname}";
$userPhone = $order->phone ?? $order->user?->phone;

$address  = data_get($order, 'address.address', '');
$position = $order?->currency?->position;
$symbol   = $order?->currency?->symbol;
$products = [];

foreach ($order->orderDetails as $orderDetail) {

    $title = "{$orderDetail->stock?->product?->translation?->title} (";

    foreach ($orderDetail->stock?->stockExtras?->sortDesc() as $item) {
        $title .= "{$item->group?->translation?->title}: {$item->value?->value}, ";
    }

    $title = rtrim($title, ', ');

    $products[] = [
        'id'                => $orderDetail->stock->product->id,
        'title'             => "$title)",
        'rate_tax'          => $orderDetail->rate_tax,
        'quantity'          => $orderDetail->quantity,
        'rate_discount'     => $orderDetail->rate_discount,
        'rate_origin_price' => $orderDetail->rate_origin_price,
        'rate_total_price'  => $orderDetail->rate_total_price,
    ];
}

?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, shrink-to-fit=no"
    >
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ __('errors.' . ResponseError::ORDER, locale: $lang) }} {{$order?->id}}</title>
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css"
        integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm"
        crossorigin="anonymous"
    >
    <style>
        html {
            -webkit-box-sizing: border-box;
            box-sizing: border-box;
        }

        .logo {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            overflow: hidden;
        }

        .subtitle {
            margin-top: 50px;
        }

        .space {
            margin-top: 300px;
        }
    </style>
</head>
<body>
<div class="container d-flex justify-content-between">
    <div class="float-left">
        <img class="logo" src="{{$logo}}" alt="logo"/>
    </div>
    <div class="float-right">
        <h1 class="title">{{ __('errors.' . ResponseError::INVOICE, locale: $lang) }} #{{ $order->id }}</h1>
        <h2 class="title gray">{{ $order->created_at?->format('Y-m-d') }}</h2>
    </div>
</div>
<div class="container d-flex justify-content-between" style="margin-top: 100px">
    <div class="float-left" style="margin-right: 50px">
        <h3 class="subtitle">{{ __('errors.' . ResponseError::ADDRESS_PLACE, locale: $lang) }}</h3>
        <div class="address__info">
            <div class="address__info--item">{!! $userName !!}</div>
            <div class="address__info--item">{!! $address !!}</div>
            <div class="address__info--item">{!! data_get($order, 'address.floor', '') .
                data_get($order, 'address.house', '') . data_get($order, 'address.office', '') !!}
            </div>
            <div class="address__info--item">
                {!! !empty($userPhone) ? '+' . str_replace('+', '', $userPhone) : '' !!}
            </div>
        </div>
    </div>
</div>
<div class="space"></div>
<table class="table table-striped mt-4 table-bordered">
    <thead>
    <tr>
        <th scope="col">#</th>
        <th scope="col">{{ __('errors.' . ResponseError::FROM, locale: $lang) }}</th>
        <th scope="col">{{ __('errors.' . ResponseError::NUMBER, locale: $lang) }}</th>
        <th scope="col">{{ __('errors.' . ResponseError::DATE, locale: $lang) }}</th>
    </tr>
    </thead>
    <tbody>
    <tr>
        <th scope="row">{{ $order->id }}</th>
        <td>{{ $order->created_at?->format('Y-m-d') }}</td>
        <td>{{ $order->id }}</td>
        <td>{{ $order->created_at?->format('Y-m-d') }}</td>
    </tr>
    </tbody>
</table>
<table class="table table-striped mt-4 table-bordered"> {{-- style="page-break-after: always;" --}}
    <thead>
    <tr>
        <th scope="col">#</th>
        <th scope="col">{{ __('errors.' . ResponseError::PRODUCT, locale: $lang) }}</th>
        <th scope="col">{{ __('errors.' . ResponseError::TOTAL_TAX, locale: $lang) }}</th>
        <th scope="col">{{ __('errors.' . ResponseError::PRICE_WITHOUT_TAX, locale: $lang) }}</th>
        <th scope="col">{{ __('errors.' . ResponseError::QUANTITY, locale: $lang) }}</th>
        <th scope="col">{{ __('errors.' . ResponseError::DISCOUNT, locale: $lang) }}</th>
        <th scope="col">{{ __('errors.' . ResponseError::TOTAL_PRICE_WITHOUT_TAX, locale: $lang) }}</th>
    </tr>
    </thead>
    <tbody>
        @foreach($products as $product)
            <tr>
                <th scope="row">#{{ $product['id'] ?? 0 }}</th>
                <td>{{ $product['title'] ?? 'no name' }}</td>
                <td>{{ number_format(@($product['rate_tax']) ?? 0, 2) }}</td>
                <td>
                    {{ $position === 'before' ? $symbol : '' }}
                    {{ number_format($product['rate_origin_price'] ?? 0, 2)  }}
                    {{ $position === 'after' ? $symbol : '' }}
                </td>
                <td>{{ $product['quantity'] ?? 0 }}</td>
                <td>{{ $product['rate_discount'] ?? 0 }}</td>
                <td>
                    {{ $position === 'before' ? $symbol : '' }}
                    {{ number_format(@($product['rate_total_price']) ?? 0, 2) }}
                    {{ $position === 'after' ? $symbol : '' }}
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
<table class="table table-striped mt-4 table-bordered">
    <thead>
    <tr>
        <th scope="col">{{ __('errors.' . ResponseError::DETAILS, locale: $lang) }}</th>
        <th scope="col">{{ __('errors.' . ResponseError::TAX, locale: $lang) }} %</th>
        <th scope="col">{{ $position === 'before' ? $symbol : '' }} {{ __('errors.' . ResponseError::TAX, locale: $lang) }} {{ $position === 'after' ? $symbol : '' }}</th>
    </tr>
    </thead>
    <tbody>
    <tr>
        <th scope="row">{{$order->shop?->translation?->title}}</th>
        <td>{{ $order->shop?->tax }}</td>
        <td>{{ number_format($order->rate_total_tax, 2) }}</td>
    </tr>
    </tbody>
</table>
<table class="table table-striped table-bordered">
    <thead>
    <tr>
        <th scope="col">{{ __('errors.' . ResponseError::PRICE, locale: $lang) }}</th>
        <th scope="col">{{ __('errors.' . ResponseError::DELIVERY_FEE, locale: $lang) }}</th>
        <th scope="col">{{ __('errors.' . ResponseError::PRICE, locale: $lang) }}</th>
        <th scope="col">{{ __('errors.' . ResponseError::COUPON, locale: $lang) }}</th>
        <th scope="col">
            {{ $position === 'before' ? $symbol : '' }} {{ __('errors.' . ResponseError::TOTAL_PRICE, locale: $lang) }} {{ $position === 'after' ? $symbol : '' }}
        </th>
    </tr>
    </thead>
    <tbody>
    <tr>
        <th scope="row">
            {{ $position === 'before' ? $symbol : '' }}
            {{ number_format(($order->rate_total_price - $order->rate_total_tax), 2) }}
            {{ $position === 'after' ? $symbol : '' }}
        </th>
        <td>
            {{ $position === 'before' ? $symbol : '' }}
            {{ number_format($order->rate_delivery_fee, 2) }}
            {{ $position === 'after' ? $symbol : '' }}
        </td>
        <td>
            {{ $position === 'before' ? $symbol : '' }}
            {{ number_format($order->rate_total_price, 2) }}
            {{ $position === 'after' ? $symbol : '' }}
        </td>
        <td>
            {{ $position === 'before' ? $symbol : '' }}
            {{ number_format($order->rate_coupon_price, 2) }}
            {{ $position === 'after' ? $symbol : '' }}
        </td>
        <td>
            {{ $position === 'before' ? $symbol : '' }}
            {{ number_format($order->rate_total_price, 2) }}
            {{ $position === 'after' ? $symbol : '' }}
        </td>
    </tr>
    </tbody>
</table>
<div class="container float-left">
    <p><b>{{ __('errors.' . ResponseError::DELIVERY_DATE_TIME, locale: $lang) }}:</b> {{ $order->delivery_date }}</p>
</div>

<script
    src="https://code.jquery.com/jquery-3.2.1.slim.min.js"
    integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN"
    crossorigin="anonymous">
</script>

<script
    src="https://cdn.jsdelivr.net/npm/popper.js@1.12.9/dist/umd/popper.min.js"
    integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q"
    crossorigin="anonymous">
</script>

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js"
    integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl"
    crossorigin="anonymous">
</script>

</body>
</html>
