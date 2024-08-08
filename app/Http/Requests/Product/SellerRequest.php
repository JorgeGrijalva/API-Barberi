<?php
declare(strict_types=1);

namespace App\Http\Requests\Product;

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
            'category_id'           => [
                'required',
                Rule::exists('categories', 'id')
                    
                    ->where('active', true),
            ],
            'brand_id'              => ['nullable', Rule::exists('brands', 'id')
            ],
            'unit_id'               => [
                'nullable',
                Rule::exists('units', 'id')->where('active', true)
            ],
            'parent_id'             => [
                'nullable',
                Rule::exists('products', 'id')->whereNull('parent_id')
            ],
            'keywords'              => 'string',
            'images'                => 'array',
            'images.*'              => 'string',
            'previews'              => 'array',
            'previews.*'            => 'string',
            'title'                 => ['required', 'array'],
            'title.*'               => ['required', 'string', 'min:1', 'max:191'],
            'description'           => 'array',
            'description.*'         => 'string|min:1',
            'tax'                   => 'numeric',
            'min_qty'               => 'integer|min:0',
            'max_qty'               => 'integer|min:0',
            'qr_code'               => [
                'string',
                Rule::unique('products','qr_code')
                    ->ignore(request()->route('product'),'uuid')
            ],
            'active'                => 'boolean',
            'price'                 => 'numeric',
            'interval'              => 'numeric',
            'digital'               => 'required|boolean',
            'age_limit'             => 'required|numeric',
            'meta'                  => 'array',
            'meta.*'                => 'array',
            'meta.*.path'           => 'string',
            'meta.*.title'          => 'required|string',
            'meta.*.keywords'       => 'string',
            'meta.*.description'    => 'string',
            'meta.*.h1'             => 'string',
            'meta.*.seo_text'       => 'string',
            'meta.*.canonical'      => 'string',
            'meta.*.robots'         => 'string',
            'meta.*.change_freq'    => 'string',
            'meta.*.priority'       => 'string',
        ];
    }
}
