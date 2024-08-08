<?php
declare(strict_types=1);

namespace App\Http\Requests\Booking;

use App\Http\Requests\BaseRequest;
use App\Models\Booking;
use Illuminate\Validation\Rule;

class MasterUpdateRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'user_id' => [
                'integer',
                Rule::exists('users', 'id')
            ],
            'payment_id' => [
                'integer',
                Rule::exists('payments', 'id')
            ],
            'currency_id' => [
                'integer',
                Rule::exists('currencies', 'id')->where('active', true)
            ],
//            'start_date' => 'date_format:Y-m-d H:i',
//            'end_date'   => 'date_format:Y-m-d H:i',
            'service_master_id' => [
                'integer',
                Rule::exists('service_masters', 'id')->where('active', true)->where('master_id', auth('sanctum')->id())
            ],
            'price_id' => [
//                'required',
                'integer',
                Rule::exists('service_master_prices', 'id')
            ],
            'service_extras'   => 'array',
            'service_extras.*' => [
                'integer',
                Rule::exists('service_extras', 'id')->where('active', true)
            ],
            'next_times_update' => 'bool',
            'note'    => 'string',
            'data'    => 'array',
            'gender'  => ['int', Rule::in(Booking::GENDERS)],
            'notes'   => 'array',
            'notes.*' => 'string'
        ];
    }
}
