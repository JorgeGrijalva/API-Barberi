<?php
declare(strict_types=1);

namespace App\Http\Requests\DeliveryPointWorkingDay;

use App\Helpers\Utility;
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
            'delivery_point_id' => [
                'required',
                'integer',
                Rule::exists('delivery_points', 'id')
            ],
            'dates'             => 'array|max:7',
            'dates.*.from'      => 'required|string|min:1|max:5|date_format:H:i',
            'dates.*.to'        => 'required|string|min:1|max:5|date_format:H:i',
            'dates.*.disabled'  => 'boolean',
            'dates.*.day'       => ['required', Rule::in(Utility::DAYS)],
        ];
    }
}
