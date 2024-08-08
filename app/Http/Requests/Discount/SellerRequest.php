<?php
declare(strict_types=1);

namespace App\Http\Requests\Discount;

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
            'type'          => 'required|in:fix,percent',
            'price'         => 'numeric',
            'start'         => 'date_format:Y-m-d',
            'end'           => 'required|date_format:Y-m-d',
            'active'        => 'boolean',
            'images'        => 'array',
            'images.*'      => 'required|string',
            'stocks'        => 'array|required',
            'stocks.*'      => [
                'required',
                'integer',
                Rule::exists('stocks', 'id'),
            ],
        ];
    }
}
