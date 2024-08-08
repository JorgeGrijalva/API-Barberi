<?php
declare(strict_types=1);

namespace App\Http\Requests\Cart;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class GroupStoreRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'stock_id' =>  [
                'required',
                'integer',
                Rule::exists('stocks', 'id')
            ],
            'images'         => 'array',
            'images.*'       => 'string',
            'quantity'       => 'required|numeric|min:1',
            'cart_id'        => 'required|integer|exists:carts,id',
            'user_cart_uuid' => 'required|string|exists:user_carts,uuid',
            'name'           => 'string',
        ];
    }

}
