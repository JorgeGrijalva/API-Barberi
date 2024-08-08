<?php
declare(strict_types=1);

namespace App\Http\Requests\ShopLocation;

use App\Http\Requests\BaseRequest;
use App\Models\ShopLocation;
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
            'region_id' => [
                'required',
                'integer',
                Rule::exists('regions', 'id')
            ],
            'country_id' => [
                'required',
                'integer',
                Rule::exists('countries', 'id')
            ],
            'city_id' => [
                'integer',
                Rule::exists('cities', 'id')
            ],
            'area_id' => [
                'integer',
                Rule::exists('areas', 'id')
            ],
            'type' => [
                'integer',
                Rule::in(ShopLocation::TYPES),
            ],
        ];
    }
}
