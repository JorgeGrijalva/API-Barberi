<?php
declare(strict_types=1);

namespace App\Http\Requests\Cart;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class StoreRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'cart_id'  => 'nullable|integer|exists:carts,id',
            'stock_id' => [
                'required',
                'integer',
                Rule::exists('stocks', 'id')
            ],
            'images'   => 'array',
            'images.*' => 'string',
            'quantity' => 'required|numeric',
            'group'    => 'boolean',
            'currency_id' => [
                'required',
                'integer',
                Rule::exists('currencies', 'id')
            ],
            'region_id'     => ['required', 'integer', Rule::exists('regions', 'id')],
            'country_id'    => ['required', 'integer', Rule::exists('countries', 'id')->where('region_id', request('region_id'))],
            'city_id'       => ['integer', Rule::exists('cities', 'id')->where('country_id', request('country_id'))],
            'area_id'       => ['integer', Rule::exists('areas', 'id')->where('city_id', request('city_id'))],
        ];
    }
}

