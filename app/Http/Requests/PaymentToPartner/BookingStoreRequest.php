<?php

namespace App\Http\Requests\PaymentToPartner;

use App\Http\Requests\BaseRequest;
use App\Models\Booking;
use Illuminate\Validation\Rule;

class BookingStoreRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'data'   => 'array|required',
            'data.*' => [
                'required',
                'integer',
                Rule::exists('bookings', 'id')
                    ->where('status', Booking::STATUS_ENDED)
            ],
            'payment_id' => [
                'required',
                'integer',
                Rule::exists('payments', 'id')
                    ->where('active', true)
            ],
        ];
    }
}
