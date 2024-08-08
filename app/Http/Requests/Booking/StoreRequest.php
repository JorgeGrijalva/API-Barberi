<?php
declare(strict_types=1);

namespace App\Http\Requests\Booking;

use App\Http\Requests\BaseRequest;
use App\Models\Booking;
use App\Models\Coupon;
use App\Models\Transaction;
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
            'payment_id' => [
                'integer',
                Rule::exists('payments', 'id')
            ],
            'currency_id' => [
                'integer',
                Rule::exists('currencies', 'id')->where('active', true)
            ],
            'user_gift_cart_id' => [
                'integer',
                Rule::exists('user_gift_carts', 'id')
            ],
            'coupon' => [
                'string',
                Rule::exists('coupons', 'name')->where('for', Coupon::bookingTotalPrice)
            ],
            'start_date'          => 'required|date_format:Y-m-d H:i',
            'transaction_status'  => ['string', Rule::in(Transaction::STATUSES)],
            'ids'   => 'array',
            'ids.*' => 'integer|exists:bookings,id',
            'data' => 'required|array',
            'data.*.service_master_id' => [
                'required',
                'integer',
                Rule::exists('service_masters', 'id')->where('active', true)
            ],
            'data.*.price_id' => [
//                'required',
                'integer',
                Rule::exists('service_master_prices', 'id')
            ],
            'data.*.service_extras'   => 'array',
            'data.*.service_extras.*' => [
                'integer',
                Rule::exists('service_extras', 'id')->where('active', true)
            ],
            'data.*.user_member_ship_id' => [
                'integer',
                Rule::exists('user_member_ships', 'id')->where('user_id', auth('sanctum')->id())
            ],
            'data.*.note'    => 'string|nullable',
            'data.*.data'    => 'array',
            'data.*.gender'  => ['int', Rule::in(Booking::GENDERS)],
            'data.*.notes'   => 'array',
            'data.*.notes.*' => 'string|nullable'
        ];
    }
}
