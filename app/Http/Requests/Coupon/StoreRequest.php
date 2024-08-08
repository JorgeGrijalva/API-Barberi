<?php
declare(strict_types=1);

namespace App\Http\Requests\Coupon;

use App\Http\Requests\BaseRequest;
use App\Models\Coupon;
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
            'name'          => [
                'required',
                'string',
                Rule::unique('coupons', 'name')->ignore(request()->route('coupon'))
            ],
            'type'          => ['required', 'string', Rule::in('fix', 'percent')],
            'for'           => ['string', Rule::in(Coupon::FOR)],
            'qty'           => 'required|numeric|min:1',
            'price'         => 'required|numeric|min:1',
            'expired_at'    => 'required|date_format:Y-m-d',
            'images'        => ['array'],
            'images.*'      => ['string'],
            'title'         => ['required', 'array'],
            'title.*'       => ['required', 'string', 'min:2', 'max:191'],
            'description'   => ['array'],
            'description.*' => ['string', 'min:2'],
        ];
    }
}

