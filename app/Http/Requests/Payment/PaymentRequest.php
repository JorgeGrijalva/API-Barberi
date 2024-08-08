<?php
declare(strict_types=1);

namespace App\Http\Requests\Payment;

use App\Http\Requests\BaseRequest;
use App\Http\Requests\Booking\StoreRequest as BookingStoreRequest;
use App\Http\Requests\Order\StoreRequest;
use App\Models\BookingExtraTime;
use Illuminate\Validation\Rule;
use Log;
use ReflectionClass;

class PaymentRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $userId         = auth('sanctum')->id();

        $cartId         = request('cart_id');
        $parcelId       = request('parcel_id');
        $bookingId      = request('booking_id');
        $tips           = request('tips');

        $rules = [];

        if ($cartId) {
            $rules = (new StoreRequest)->rules();
        } else if ($parcelId) {
            $rules = (new BookingStoreRequest)->rules();
        } else if ($bookingId && request('extra_time')) {
            $rules = [
                'price'         => 'required|numeric',
                'duration'      => 'required|int',
                'duration_type' => [
                    'required|string',
                    Rule::in(BookingExtraTime::DURATION_TYPES)
                ],
            ];
        }

        $reflectionClass = new ReflectionClass('Iyzipay\Model\PaymentChannel');
        $constants = $reflectionClass->getConstants();

        return [
            'cart_id' => [
                Rule::exists('carts', 'id')->where('owner_id', $userId)
            ],
            'booking_id' => [
                Rule::exists('bookings', 'id')->where('user_id', $userId)->when(!$tips || !request('extra_time'), fn($q) => $q->whereNull('parent_id'))
            ],
            'gift_cart_id' => [
                Rule::exists('gift_carts', 'id')->where('active', true)
            ],
            'member_ship_id' => [
                Rule::exists('member_ships', 'id')->where('active', true)
            ],
            'parcel_id' => [
                Rule::exists('parcel_orders', 'id')->where('user_id', $userId)
            ],
            'subscription_id' => [
                Rule::exists('subscriptions', 'id')->where('active', true)
            ],
            'ads_package_id' => [
                Rule::exists('ads_packages', 'id')->where('active', true)
            ],
            'wallet_id' => [
                Rule::exists('wallets', 'id')->where('user_id', auth('sanctum')->id())
            ],
            'total_price' => [
                'numeric'
            ],
            'tips'         => 'max:100',
            'holder_name'  => 'string|min:5|max:255',
            'card_number'  => 'numeric',
            'expire_month' => 'numeric|max:12',
            'expire_year'  => 'int',
            'cvc' 		   => 'string|max:255',
            'chanel' 	   => 'string|in:' . implode(',', $constants),
        ] + $rules;
    }

}
