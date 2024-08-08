<?php
declare(strict_types=1);

namespace App\Http\Requests\Invitation;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class SellerRequest extends BaseRequest
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
                'int',
                Rule::exists('users', 'id')
            ],
            'role' => [
                'required',
                'string',
                Rule::in(['manager', 'master', 'deliveryman'])
            ],
        ];
    }
}
