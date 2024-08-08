<?php
declare(strict_types=1);

namespace App\Http\Requests\Booking;

use App\Http\Requests\BaseRequest;

class TimesUpdateRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'start_date'        => 'required|date_format:Y-m-d H:i',
            'end_date'          => 'required|date_format:Y-m-d H:i',
            'next_times_update' => 'required|bool',
        ];
    }
}
