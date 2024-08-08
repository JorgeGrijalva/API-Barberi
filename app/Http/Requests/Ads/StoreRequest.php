<?php
declare(strict_types=1);

namespace App\Http\Requests\Ads;

use App\Http\Requests\BaseRequest;
use App\Models\AdsPackage;
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
            'active'        => 'required|boolean',
            'type'          => ['required', 'string', Rule::in(AdsPackage::TYPES)],
            'position_page' => 'integer',
            'product_limit' => 'integer',
            'time_type'     => ['required', 'string', Rule::in(AdsPackage::TIME_TYPES)],
            'time'          => 'required|integer',
            'price'         => 'required|numeric',
            'images'        => ['array'],
            'images.*'      => ['string'],
            'title'         => ['array'],
            'title.*'       => ['string', 'max:191'],
            'description'   => ['array'],
            'description.*' => ['string'],
            'button_text'   => ['array'],
            'button_text.*' => ['string'],
        ];
    }
}
