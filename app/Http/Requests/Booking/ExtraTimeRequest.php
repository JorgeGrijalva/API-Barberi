<?php
declare(strict_types=1);

namespace App\Http\Requests\Booking;

use App\Http\Requests\BaseRequest;
use App\Models\BookingExtraTime;
use Illuminate\Validation\Rule;

class ExtraTimeRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'id'            => 'int|exists:booking_extra_times,id',
            'price'         => 'required|numeric|min:0',
            'duration'      => 'required|int|min:0',
            'duration_type' => ['required', 'string', Rule::in(BookingExtraTime::DURATION_TYPES)],
            'remove_ids'    => 'array',
            'remove_ids.*'  => ['int', Rule::exists('booking_extra_times', 'id')],
        ];
    }
}
