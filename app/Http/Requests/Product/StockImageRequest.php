<?php
declare(strict_types=1);

namespace App\Http\Requests\Product;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class StockImageRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'data'    => 'required|array',
            'data.*.id' => [
                'required',
                Rule::exists('stocks', 'id')
            ],
            'data.*.images'   => 'array',
            'data.*.images.*' => 'string',
        ];
    }
}
