<?php
declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class BrandCreateRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'shop_id'               => ['int', Rule::exists('shops', 'id')],
            'active'                => ['numeric', Rule::in(1,0)],
            'title'                 => ['required', 'string', 'min:2'],
            'images'                => ['array'],
            'images.*'              => ['string'],
            'meta'                  => ['array'],
            'meta.*'                => ['array'],
            'meta.*.path'           => ['string'],
            'meta.*.title'          => ['required', 'string'],
            'meta.*.keywords'       => ['string'],
            'meta.*.description'    => ['string'],
            'meta.*.h1'             => ['string'],
            'meta.*.seo_text'       => ['string'],
            'meta.*.canonical'      => ['string'],
            'meta.*.robots'         => ['string'],
            'meta.*.change_freq'    => ['string'],
            'meta.*.priority'       => ['string'],
        ];
    }
}
