<?php
declare(strict_types=1);

namespace App\Http\Requests\DeliveryManSetting;

use App\Http\Requests\BaseRequest;
use App\Models\DeliveryManSetting;
use Illuminate\Validation\Rule;

class AdminRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'user_id' => [
                'required',
                'integer',
                Rule::unique('deliveryman_settings', 'user_id')
                    ->ignore(data_get(DeliveryManSetting::find(request()->route('deliveryman_setting')), 'user_id'), 'user_id'),
                Rule::exists('users', 'id')
                    
            ],
            'type_of_technique'     => ['string', Rule::in(DeliveryManSetting::TYPE_OF_TECHNIQUES)],
            'brand'                 => 'string',
            'model'                 => 'string',
            'number'                => 'string',
            'color'                 => 'string',
            'online'                => 'boolean',
            'region_id'             => ['required', 'integer', Rule::exists('regions', 'id')],
            'country_id'            => ['required', 'integer', Rule::exists('countries', 'id')->where('region_id', request('region_id'))],
            'city_id'               => ['integer', Rule::exists('cities', 'id')->where('country_id', request('country_id'))],
            'area_id'               => ['integer', Rule::exists('areas', 'id')->where('city_id', request('city_id'))],
            'location'              => 'array',
            'location.latitude'     => is_array(request('location')) ? 'required|numeric' : 'numeric',
            'location.longitude'    => is_array(request('location')) ? 'required|numeric' : 'numeric',
            'images'                => 'array',
            'images.*'              => 'string',
        ];
    }
}
