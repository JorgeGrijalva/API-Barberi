<?php
declare(strict_types=1);

namespace App\Http\Requests\UserWorkingDay;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class AdminRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')
            ],
        ] + (new StoreRequest)->rules();
    }
}
