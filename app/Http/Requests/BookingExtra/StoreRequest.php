<?php
declare(strict_types=1);

namespace App\Http\Requests\BookingExtra;

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
            'booking_id' => [
                'required',
                'int',
                Rule::exists('bookings', 'id')
            ],
            'service_extra_id' => [
                'required',
                'int',
                Rule::exists('service_extras', 'id')->where('active', 'true')
            ],
        ];
    }
}
