<?php
declare(strict_types=1);

namespace App\Http\Requests\Order;

use App\Http\Requests\BaseRequest;
use App\Models\Order;
use Illuminate\Validation\Rule;

class StocksCalculateRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'currency_id'           => 'numeric|exists:currencies,id',
            'coupon'                => 'array',
            'coupon.*'              => 'string',
            'delivery_type'         => [Rule::in(Order::DELIVERY_TYPES)],
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
            'products'              => 'required|array',
            'products.*.stock_id'   =>  [
                'required',
                'integer',
                Rule::exists('stocks', 'id')
            ],
            'products.*.quantity'   => 'required|integer',
            'products.*.image'      => 'string',
        ];
    }
}
