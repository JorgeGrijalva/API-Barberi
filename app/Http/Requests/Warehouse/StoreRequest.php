<?php
declare(strict_types=1);

namespace App\Http\Requests\Warehouse;

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
            'region_id'          => [
                'required',
                'integer',
                Rule::exists('regions', 'id')
            ],
            'country_id'         => [
                'required',
                'integer',
                Rule::exists('countries', 'id')
            ],
            'city_id'            => [
                'integer',
                Rule::exists('cities', 'id')
            ],
            'area_id'            => [
                'integer',
                Rule::exists('areas', 'id')
            ],
            'address'            => 'required|array',
            'location'           => 'required|array',
            'location.latitude'  => 'required|numeric',
            'location.longitude' => 'required|numeric',
            'images'             => 'array',
            'images.*'           => 'string',
            'title'              => 'array',
            'title.*'            => 'string|max:191',
            'description'        => 'array',
            'description.*'      => 'string'
        ];
    }
}
