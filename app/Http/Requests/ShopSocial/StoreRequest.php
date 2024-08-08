<?php
declare(strict_types=1);

namespace App\Http\Requests\ShopSocial;

use App\Http\Requests\BaseRequest;
use App\Models\ShopSocial;
use Illuminate\Validation\Rule;

class StoreRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'data.*.type'      => ['required', Rule::in(ShopSocial::TYPES)],
            'data.*.content'   => 'required|string',
            'data.*.images'    => 'array',
            'data.*.images.*'  => 'string',
        ];
    }
}
