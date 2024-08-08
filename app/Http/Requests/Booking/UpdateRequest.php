<?php
declare(strict_types=1);

namespace App\Http\Requests\Booking;

use App\Http\Requests\BaseRequest;
use App\Models\Booking;
use App\Models\Transaction;
use Illuminate\Validation\Rule;

class UpdateRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'service_master_id' => [
                'integer',
                Rule::exists('service_masters', 'id')->where('active', true)
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
            'currency_id' => [
                'integer',
                Rule::exists('currencies', 'id')->where('active', true)
            ],
            'user_gift_cart_id' => [
                'integer',
                Rule::exists('gift_carts', 'id')->where('active', true)
            ],
            'user_member_ship_id' => [
                'integer',
                Rule::exists('user_member_ships', 'id')
                    ->where('user_id', auth('sanctum')->id())
            ],
            'transaction_status'  => ['string', Rule::in(Transaction::STATUSES)],
            'next_times_update'   => 'bool',
            'note'                => 'string',
            'start_date' => 'date_format:Y-m-d H:i',
            'end_date'   => 'date_format:Y-m-d H:i',
            'data'       => 'array',
            'gender'     => ['int', Rule::in(Booking::GENDERS)],
            'notes'      => 'array',
            'notes.*'    => 'string',
        ];
    }
}
