<?php
declare(strict_types=1);

namespace App\Http\Requests\Banner;

use App\Http\Requests\BaseRequest;
use App\Models\Banner;
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
            'products'         => 'array',
            'products.*'       => [
                'required',
                'integer',
                Rule::exists('products', 'id')
            ],
            'type'          => Rule::in(Banner::TYPES),
            'url'           => 'string',
            'clickable'     => 'boolean',
            'active'        => 'boolean',
            'input'         => 'integer|min:0',
            'images'        => ['array'],
            'images.*'      => ['string'],
            'previews'      => 'array',
            'previews.*'    => 'string',
            'title'         => ['array'],
            'title.*'       => ['string', 'max:191'],
            'description'   => ['array'],
            'description.*' => ['string'],
            'button_text'   => ['array'],
            'button_text.*' => ['string'],
        ];
    }
}
