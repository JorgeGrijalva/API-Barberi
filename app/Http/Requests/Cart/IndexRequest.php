<?php
declare(strict_types=1);

namespace App\Http\Requests\Cart;

use App\Http\Requests\BaseRequest;

class IndexRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'user_cart_uuid' => 'required|string|exists:user_carts,uuid'
        ];
    }

}
