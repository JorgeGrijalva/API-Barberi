<?php
declare(strict_types=1);

namespace App\Http\Requests\Booking;

use App\Helpers\GetShop;
use App\Http\Requests\BaseRequest;
use App\Models\Booking;
use App\Models\Coupon;
use Illuminate\Validation\Rule;

class MasterStoreRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $shopId = GetShop::shop()?->id;

        return [
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')
            ],
            'payment_id' => [
                'integer',
                Rule::exists('payments', 'id')
            ],
            'user_gift_cart_id' => [
                'integer',
                Rule::exists('gift_carts', 'id')->where('active', true)
            ],
            'currency_id' => [
                'integer',
                Rule::exists('currencies', 'id')->where('active', true)
            ],
            'coupon' => [
                'string',
                Rule::exists('coupons', 'name')
                    ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
                    ->where('for', Coupon::bookingTotalPrice)
            ],
            'start_date' => 'required|date_format:Y-m-d H:i',
            'ids'   => 'array',
            'ids.*' => 'integer|exists:bookings,id',
            'data' => [
                'required',
                'array',
            ],
            'data.*.service_master_id' => [
                'required',
                'integer',
                Rule::exists('service_masters', 'id')
                    ->where('active', true)
                    ->where('master_id', auth('sanctum')->id())
            ],
            'data.*.price_id' => [
//                'required',
                'integer',
                Rule::exists('service_master_prices', 'id')
            ],
            'data.*.service_extras'   => 'array',
            'data.*.service_extras.*' => [
                'integer',
                Rule::exists('service_extras', 'id')
                    ->where('active', true)
                    ->when($shopId, fn($q, $shopId) => $q->where('shop_id', $shopId))
            ],
            'data.*.note'       => 'string',
            'data.*.data'       => 'array',
            'data.*.gender'     => ['int', Rule::in(Booking::GENDERS)],
            'data.*.notes'      => 'array',
            'data.*.notes.*'    => 'string'
        ];
    }
}
