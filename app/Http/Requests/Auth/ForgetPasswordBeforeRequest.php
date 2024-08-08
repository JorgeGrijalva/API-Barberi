<?php
declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseRequest;

class ForgetPasswordBeforeRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     * @return array
     */
    public function rules(): array
	{
		return [
            'id'    => 'required|string',
            'phone' => 'required|numeric|exists:users,phone',
		];
	}
}
