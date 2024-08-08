<?php
declare(strict_types=1);

namespace App\Http\Requests\UserAddress;

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
            'title'                 => 'string|max:255',
            'address'               => 'array',
            'location'              => 'array',
            'active'                => 'boolean',
            'firstname'             => 'required|string',
            'lastname'              => 'required|string',
            'phone'                 => 'required|string',
            'zipcode'               => 'required|string',
            'street_house_number'   => 'required|string',
            'additional_details'    => 'nullable|string|max:191',
            'location.latitude'     => 'numeric',
            'location.longitude'    => 'numeric',
            'region_id'             => ['required', 'integer', Rule::exists('regions', 'id')],
            'country_id'            => ['required', 'integer', Rule::exists('countries', 'id')->where('region_id', request('region_id'))],
            'city_id'               => ['integer', Rule::exists('cities', 'id')->where('country_id', request('country_id'))],
            'area_id'               => ['integer', Rule::exists('areas', 'id')->where('city_id', request('city_id'))],
        ];
    }
}
