<?php
declare(strict_types=1);

namespace App\Http\Requests\Order;

use App\Http\Requests\BaseRequest;
use App\Models\Order;
use Illuminate\Validation\Rule;

class StoreRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request
     * @return array
     */
    public function rules(): array
    {
        return [
            'user_id'                       => 'integer|exists:users,id',
            'currency_id'                   => 'required|integer|exists:currencies,id',
            'payment_id'                    => [
                'integer',
                Rule::exists('payments', 'id')->whereIn('tag', ['wallet', 'cash'])
            ],
            'rate'                          => 'numeric',
            'delivery_type'                 => ['required', Rule::in(Order::DELIVERY_TYPES)],
            'coupon'                        => 'array',
            'coupon.*'                      => 'string',
            'location'                      => 'array',
            'location.latitude'             => 'numeric',
            'location.longitude'            => 'numeric',
            'address'                       => 'array',
            'phone'                         => 'string',
            'username'                      => 'string',
            'delivery_date'                 => 'date|date_format:Y-m-d H:i',
            'cart_id'                       => 'integer|exists:carts,id',

            'notes'                         => 'array',
            'notes.order'                   => 'array',
            'notes.product'                 => 'array',
            'notes.order.*'                 => 'required|string|max:255',
            'notes.product.*'               => 'required|string|max:255',

            'images'                        => 'array',
            'images.*'                      => 'array',
            'images.*.*'                    => 'required|string|max:255',

            'data'                          => 'nullable|array',
            'data.*.shop_id'                => 'required|exists:shops,id',
            'data.*.products'               => 'required|array',
            'data.*.products.*.stock_id'    =>  [
                'required',
                'integer',
                Rule::exists('stocks', 'id'),
            ],
            'data.*.products.*.quantity'    => 'required|integer',
            'data.*.products.*.note'        => 'nullable|string|max:255',
            'data.*.products.*.images'      => 'array',
            'data.*.products.*.images.*'    => 'string',
            'address_id'                    => [
                'integer',
                Rule::exists('user_addresses', 'id')
                    ->where('user_id', request('user_id', auth('sanctum')->id()))
            ],
            'delivery_price_id'     => [
                request('delivery_type') === Order::DELIVERY ? 'required' : 'nullable',
                'integer',
                Rule::exists('delivery_prices', 'id')
            ],
            'delivery_point_id'     => [
                request('delivery_type') === Order::POINT ? 'required' : 'nullable',
                'integer',
                Rule::exists('delivery_points', 'id')
            ],
        ];
    }
}
