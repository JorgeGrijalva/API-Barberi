<?php
declare(strict_types=1);

namespace App\Http\Requests\GiftCart;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class SendRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'user_gift_cart_id' => [
                'required',
                Rule::exists('user_gift_carts', 'id')->where('user_id', auth('sanctum')->id())
            ],
            'user_id' => 'required|exists:users,id',
        ];
    }
}
