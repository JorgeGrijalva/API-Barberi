<?php
declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class FilterRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'sort'              => 'string|in:asc,desc',
            'column'            => 'regex:/^[a-zA-Z-_]+$/',
            'shop_ids'          => 'array',
            'shop_ids.*'        => [
                'integer',
                Rule::exists('shops', 'id')
            ],
            'lang'              => 'exists:languages,locale',
            'type'              => 'required|in:news_letter,category,most_sold',
            'category_ids'      => 'array',
            'category_ids.*'    => 'exists:categories,id',
            'brand_ids'         => 'array',
            'brand_ids.*'       => 'exists:brands,id',
            'price_from'        => 'numeric',
            'price_to'          => 'numeric',
            'rating_from'       => 'numeric',
            'rating_to'         => 'numeric',
            'extras'            => 'array',
            'extras.*'          => 'exists:extra_values,id',
        ];
    }

}
