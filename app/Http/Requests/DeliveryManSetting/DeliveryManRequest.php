<?php
declare(strict_types=1);

namespace App\Http\Requests\DeliveryManSetting;

use App\Http\Requests\BaseRequest;
use App\Models\DeliveryManSetting;
use Illuminate\Validation\Rule;

class DeliveryManRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'type_of_technique'     => ['required', 'string', Rule::in(DeliveryManSetting::TYPE_OF_TECHNIQUES)],
            'brand'                 => 'required|string',
            'model'                 => 'required|string',
            'number'                => 'required|string',
            'color'                 => 'string',
            'online'                => 'required|boolean',
            'location'              => 'array',
            'region_id'             => ['required', 'integer', Rule::exists('regions', 'id')],
            'country_id'            => ['required', 'integer', Rule::exists('countries', 'id')->where('region_id', request('region_id'))],
            'city_id'               => ['integer', Rule::exists('cities', 'id')->where('country_id', request('country_id'))],
            'area_id'               => ['integer', Rule::exists('areas', 'id')->where('city_id', request('city_id'))],
            'location.latitude'     => 'numeric',
            'location.longitude'    => 'numeric',
            'images'                => 'array',
            'images.*'              => 'string',
        ];
    }
}
