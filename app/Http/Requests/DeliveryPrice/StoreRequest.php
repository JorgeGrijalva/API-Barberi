<?php
declare(strict_types=1);

namespace App\Http\Requests\DeliveryPrice;

use App\Http\Requests\BaseRequest;
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
            'price'         => ['required', 'integer', 'min:0'],
            'region_id'     => ['required', 'integer', Rule::exists('regions', 'id')],
            'country_id'    => ['required', 'integer', Rule::exists('countries', 'id')->where('region_id', request('region_id'))],
            'city_id'       => ['integer', Rule::exists('cities', 'id')->where('country_id', request('country_id'))],
            'area_id'       => ['integer', Rule::exists('areas', 'id')->where('city_id', request('city_id'))],
            'shop_id'       => ['integer', Rule::exists('shops', 'id')],
            'title'         => ['array'],
            'title.*'       => ['string', 'max:191'],
            'description'   => ['array'],
            'description.*' => ['string'],
        ];
    }
}
