<?php
declare(strict_types=1);

namespace App\Http\Requests\Order;

use App\Http\Requests\BaseRequest;
use App\Models\Order;
use Illuminate\Validation\Rule;

class UpdateRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'user_id'               => 'integer|exists:users,id',
            'currency_id'           => 'integer|exists:currencies,id',
            'rate'                  => 'numeric',
            'delivery_type'         => [Rule::in(Order::DELIVERY_TYPES)],
            'coupon'                => 'string|max:255',
            'note'                  => 'string|max:255',
            'location'              => 'array',
            'location.latitude'     => 'numeric',
            'location.longitude'    => 'numeric',
            'address'               => 'array',
            'phone'                 => 'string',
            'username'              => 'string',
            'delivery_date'         => 'date|date_format:Y-m-d H:i',
            'track_name'            => 'string|max:255',
            'track_id'              => 'string|max:255',
            'track_url'             => 'string|max:255',
            'images'                => 'array',
            'images.*'              => 'string',
            'delivery_price_id'     => [
                'integer',
                Rule::exists('delivery_prices', 'id')
            ],
            'delivery_point_id'     => [
                'integer',
                Rule::exists('delivery_points', 'id')
            ],
            'products'              => 'array',
            'products.*.stock_id'   => [
                'required',
                'integer',
                Rule::exists('stocks', 'id'),
            ],
            'products.*.replace_stock_id' => [
                'integer',
                Rule::exists('stocks', 'id'),
            ],
            'products.*.quantity'           => 'required|integer',
            'products.*.replace_quantity'   => 'integer',
            'products.*.replace_note'       => 'string',
        ];
    }

}
