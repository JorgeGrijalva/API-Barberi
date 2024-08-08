<?php
declare(strict_types=1);

namespace App\Http\Requests\DeliveryPoint;

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
            'active'             => 'required|boolean',
            'region_id'          => ['required', 'integer', Rule::exists('regions', 'id')],
            'country_id'         => ['required', 'integer', Rule::exists('countries', 'id')->where('region_id', request('region_id'))],
            'city_id'            => ['integer', Rule::exists('cities', 'id')->where('country_id', request('country_id'))],
            'area_id'            => ['integer', Rule::exists('areas', 'id')->where('city_id', request('city_id'))],
            'price'              => 'required|numeric|min:0',
            'address'            => 'required|array',
            'location'           => 'required|array',
            'location.latitude'  => 'required|numeric',
            'location.longitude' => 'required|numeric',
            'fitting_rooms'      => 'required|integer|min:0',
            'images'             => 'array',
            'images.*'           => 'string',
            'title'              => 'array',
            'title.*'            => 'string|max:191',
            'description'        => 'array',
            'description.*'      => 'string'
        ];
    }
}
