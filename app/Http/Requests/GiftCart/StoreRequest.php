<?php

namespace App\Http\Requests\GiftCart;

use App\Http\Requests\BaseRequest;
use App\Models\MemberShip;
use Illuminate\Validation\Rule;

class StoreRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'time'            => ['required', 'string', Rule::in(MemberShip::TIMES)],
            'price'           => ['required','numeric','min:1'],
            'active'          => ['boolean'],
            'title'           => ['required', 'array'],
            'title.*'         => ['required', 'string', 'min:2', 'max:191'],
            'description'     => ['array'],
            'description.*'   => ['string', 'min:2'],
        ];
    }
}
