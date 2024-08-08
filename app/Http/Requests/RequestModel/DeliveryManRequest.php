<?php
declare(strict_types=1);

namespace App\Http\Requests\RequestModel;

use App\Http\Requests\BaseRequest;
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
            'data'	=> 'required|array',
            'data.region_id' => [
                'required',
                'integer',
                Rule::exists('regions', 'id')
            ],
            'data.country_id' => [
                'required',
                'integer',
                Rule::exists('countries', 'id')->where('region_id', request('data.region_id'))
            ],
            'data.city_id' => [
                'integer',
                Rule::exists('cities', 'id')->where('country_id', request('data.country_id'))
            ],
            'data.area_id' => [
                'integer',
                Rule::exists('areas', 'id')->where('city_id', request('data.city_id'))
            ],
        ];
    }
}
