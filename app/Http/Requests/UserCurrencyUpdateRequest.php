<?php

namespace App\Http\Requests;

class UserCurrencyUpdateRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'currency_id' => 'required|integer|exists:currencies,id',
        ];
    }
}
