<?php

namespace App\Http\Requests\Report\Booking;

use App\Http\Requests\BaseRequest;

class BookingRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'date_from' => 'date_format:Y-m-d',
            'date_to'   => 'date_format:Y-m-d',
        ];
    }
}
