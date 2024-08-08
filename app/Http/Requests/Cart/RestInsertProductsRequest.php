<?php
declare(strict_types=1);

namespace App\Http\Requests\Cart;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class RestInsertProductsRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'user_cart_uuid'        => 'required|string|exists:user_carts,uuid',
            'products'              => 'required|array',
            'products.*.stock_id'   => [
                'required',
                'integer',
                Rule::exists('stocks', 'id')
            ],
            'products.*.quantity'   => 'required|integer',
            'products.*.images'     => 'array',
            'products.*.images.*'   => 'string',
        ];
    }
}
