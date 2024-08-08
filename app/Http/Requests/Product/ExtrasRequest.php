<?php
declare(strict_types=1);

namespace App\Http\Requests\Product;

use App\Http\Requests\BaseRequest;

class ExtrasRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'extras'                 => 'required|array',
            'extras.*.ids'           => 'nullable|array',
            'extras.*.ids.*'         => 'integer|exists:extra_values,id',
            'extras.*.price'         => 'required|numeric',
            'extras.*.quantity'      => 'required|integer',
            'extras.*.sku'           => 'string|max:255',
            'extras.*.images'        => 'array',
            'extras.*.images.*'      => 'string',
            'extras.*.whole_sales'   => 'array',
            'extras.*.whole_sales.*' => 'array',
            'extras.*.whole_sales.*.min_quantity' => 'integer|max:2147483647',
            'extras.*.whole_sales.*.max_quantity' => 'integer|max:2147483647',
            'extras.*.whole_sales.*.price'        => 'numeric',
        ];
    }
}
