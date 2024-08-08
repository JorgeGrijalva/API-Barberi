<?php
declare(strict_types=1);

namespace App\Http\Requests\Translation;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class UpdateRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'product_id'    => Rule::exists('products', 'id'),
            'group'         => 'required|string',
            'value'         => 'array',
            'value.*'       => 'string',
        ];
    }
}
