<?php
declare(strict_types=1);

namespace App\Http\Requests\Report\Booking;

use App\Http\Requests\BaseRequest;

class PaymentRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'type'      => 'string|in:year,week,month,day',
            'date_from' => 'date_format:Y-m-d',
            'date_to'   => 'date_format:Y-m-d',
        ];
    }
}
