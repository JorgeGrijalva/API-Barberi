<?php
declare(strict_types=1);

namespace App\Http\Requests\DeliveryPointClosedDate;

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
            'dates'     => 'array',
            'dates.*'   => 'date_format:Y-m-d',
        ];
    }
}
