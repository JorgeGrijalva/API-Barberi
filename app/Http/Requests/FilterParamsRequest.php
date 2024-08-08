<?php
declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class FilterParamsRequest extends BaseRequest
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
            'column'            => 'regex:/^[a-zA-Z-_]+$/' ,
            'perPage'           => 'integer|min:1|max:100',
            'cPerPage'          => 'integer|min:1|max:100',
            'shop_id'           => ['integer', Rule::exists('shops', 'id')],
            'user_id'           => 'exists:users,id',
            'currency_id'       => 'exists:currencies,id',
            'lang'              => 'exists:languages,locale',
            'category_id'       => 'exists:categories,id',
            'brand_id'          => 'exists:brands,id',
            'region_id'         => 'integer',
            'country_id'        => 'integer',
            'city_id'           => 'integer',
            'area_id'           => 'integer',
            'price'             => 'numeric',
            'note'              => 'string|max:255',
            'date_from'         => 'date_format:Y-m-d',
            'date_to'           => 'date_format:Y-m-d',
            'ids'               => 'array',
            'active'            => 'boolean',
            'valid'             => 'boolean',
            'service_master_id' => 'exists:service_masters,id',
            'shop_form_options' => 'boolean',
        ];
    }

}
