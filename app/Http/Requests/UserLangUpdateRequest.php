<?php

namespace App\Http\Requests;

class UserLangUpdateRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'lang' => 'required|string|min:2'
        ];
    }
}
