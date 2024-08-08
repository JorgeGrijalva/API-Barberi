<?php
declare(strict_types=1);

namespace App\Http\Requests\Cart;

use App\Http\Requests\BaseRequest;
use App\Models\Order;
use Illuminate\Validation\Rule;

class CalculateRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'coupon'            => 'array',
            'coupon.*'          => 'string',
            'delivery_type'     => [Rule::in(Order::DELIVERY_TYPES)],
            'currency_id'       => Rule::exists('currencies', 'id'),
            'delivery_price_id' => [
                request('delivery_type') === Order::DELIVERY ? 'required' : 'nullable',
                'integer',
                Rule::exists('delivery_prices', 'id')
            ],
            'delivery_point_id' => [
                request('delivery_type') === Order::POINT ? 'required' : 'nullable',
                'integer',
                Rule::exists('delivery_points', 'id')
            ],
        ];
    }
}

