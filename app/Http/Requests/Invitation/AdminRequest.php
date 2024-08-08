<?php
declare(strict_types=1);

namespace App\Http\Requests\Invitation;

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
            'shop_id' => [
                'required',
                'int',
                Rule::exists('shops', 'id')
            ],
            'shop_name' => [
                'required',
                'string'
            ],
        ] + (new SellerRequest)->rules();
    }
}
