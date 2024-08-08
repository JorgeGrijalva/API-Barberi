<?php
declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class CouponCheckRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'coupon'  => ['required', 'string', 'min:2'],
            'user_id' => ['integer', Rule::exists('users', 'id')],
            'shop_id' => ['required', Rule::exists('shops', 'id')],
        ];
    }
}
