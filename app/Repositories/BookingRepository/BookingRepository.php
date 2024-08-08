<?php
declare(strict_types=1);

namespace App\Repositories\BookingRepository;

use App\Helpers\OrderHelper;
use App\Helpers\ResponseError;
use App\Http\Resources\ServiceExtraResource;
use App\Http\Resources\ServiceMasterResource;
use App\Http\Resources\ShopResource;
use App\Http\Resources\UserResource;
use App\Models\Booking;
use App\Models\Currency;
use App\Models\Language;
use App\Models\MemberShip;
use App\Models\ServiceExtra;
use App\Models\ServiceMaster;
use App\Models\ServiceMasterPrice;
use App\Models\Settings;
use App\Models\User;
use App\Models\UserGiftCart;
use App\Models\UserMemberShip;
use App\Repositories\CoreRepository;
use App\Repositories\UserRepository\MasterRepository;
use DateInterval;
use DateTime;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Schema;
use Throwable;

class BookingRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return Booking::class;
    }

    public function getWith(): array
    {
        $locale = Language::where('default', 1)->first()?->locale;

        return [
            'master:id,uuid,firstname,lastname,img,email,phone,r_count,r_avg,r_sum,o_count,o_sum,b_count,b_sum',
            'user:id,uuid,firstname,lastname,img,email,phone',
            'serviceMaster.service.translation' => fn($query) => $query
                ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
            'shop:id,uuid,slug,logo_img,user_id,latitude,longitude,o_count,b_count,verify',
            'shop.translation' => fn($query) => $query
                ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
            'userMemberShip',
            'extras.translation' => fn($query) => $query
                ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
            'currency',
            'activities.user:id,img,firstname,lastname',
            'extraTimes',
            'children:id,parent_id,discount,commission_fee,price,service_fee,rate,status',
            'children.review',
            'children.activities',
            'children.extraTimes',
            'transaction.paymentSystem',
            'children.transaction.paymentSystem',
        ];
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function paginate(array $filter = []): LengthAwarePaginator
    {
        $column = $filter['column'] ?? 'id';

        if ($column !== 'id') {
            $column = Schema::hasColumn('bookings', $column) ? $column : 'id';
        }

        return Booking::filter($filter)
            ->with($this->getWith())
            ->orderBy($column, $filter['sort'] ?? 'desc')
            ->paginate($filter['perPage'] ?? 10);
    }

    /**
     * @param Booking $booking
     * @return Booking
     */
    public function show(Booking $booking): Booking
    {
        return $booking->fresh($this->getWith());
    }

    /**
     * @param int $id
     * @param int|null $userId
     * @param int|null $shopId
     * @param int|null $masterId
     * @return Collection|null
     */
    public function bookingsByParentId(
        int $id,
        ?int $userId   = null,
        ?int $shopId   = null,
        ?int $masterId = null
    ): ?Collection
    {
        return $this->model()
            ->with($this->getWith())
            ->when($userId,   fn($q) => $q->where('user_id',   $userId))
            ->when($shopId,   fn($q) => $q->where('shop_id',   $shopId))
            ->when($masterId, fn($q) => $q->where('master_id', $masterId))
            ->where(fn($q) => $q->where('id', $id)->orWhere('parent_id', $id))
            ->orderBy('id', 'asc')
            ->get();
    }

    /**
     * @param array $data
     * @return array
     */
    public function calculate(array $data = []): array
    {
        try {
            return $this->prepareCalculate($data);
        } catch (Throwable $e) {
            return [
                'status'  => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * @param array $data
     * @return array
     * @throws Throwable
     */
    private function prepareCalculate(array $data = []): array
    {
        if (!isset($data['user_id'])) {
            $data['user_id'] = auth('sanctum')->id();
        }

        $locale = Language::where('default', 1)->first()?->locale;

        $rate = Currency::currenciesList()->where('active', 1)->where('default', 1)->first()?->rate ?: 1;

        if (request()->is('api/v1/dashboard/user/*') || request()->is('api/v1/rest/*')) {
            $rate = Currency::currenciesList()->find($this->currency)?->rate ?: 1;
        }

        $serviceFee = Settings::where('key', 'booking_service_fee')->first()?->value;
        $serviceFee = max((double)$serviceFee, 0) * $rate;
        $user       = User::select(['id', 'firstname', 'lastname'])->find($data['user_id']);

        $items = [];

        $startDate = new DateTime($data['start_date']);
        $startDateFormat = $startDate->format('Y-m-d');
        $endDate = new DateTime($data['start_date']);

        if (!isset($data['ids']) && $startDate < now() || $endDate < $startDate) {
            throw new Exception(__('errors.' . ResponseError::ERROR_509, locale: $this->language));
        }

        $giftCartPrice = 0;

        if (isset($data['user_gift_cart_id'])) {

            $giftCartPrice = UserGiftCart::where('user_id', $data['user_id'])
                ->where('id', $data['user_gift_cart_id'])
                ->where('expired_at', '>=', date('Y-m-d H:i:s'))
                ->first()
                ?->price * $rate;

            if (empty($giftCartPrice)) {
                throw new Exception(__('errors.' . ResponseError::ERROR_511, locale: $this->language));
            }

        }

        foreach ($data['data'] as $key => $value) {

            $serviceMaster = ServiceMaster::with([
                'master:id,firstname,lastname',
                'shop:id,uuid,slug,logo_img',
                'shop.translation' => fn($query) => $query
                    ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                        $q->where('locale', $this->language)->orWhere('locale', $locale);
                    })),
                'service:id,slug',
                'service.translation' => fn($query) => $query
                    ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                        $q->where('locale', $this->language)->orWhere('locale', $locale);
                    })),
            ])->find($value['service_master_id']);

            if (empty($serviceMaster)) {
                continue;
            }

            /** @var ServiceMaster $serviceMaster */
            $time = $serviceMaster->interval + $serviceMaster->pause;

            if ($key > 0) {

                try {
                    $startDate = new DateTime($items[$key - 1]['end_date']);
                } catch (Exception $e) {
                    return [
                        'status'  => false,
                        'message' => $e->getMessage()
                    ]; //  $e->getMessage()
                }

                $endDate = clone $startDate;
            }

            $endDate = $endDate->add(new DateInterval("PT{$time}M"));

            $value['end_date'] = $endDate->format('Y-m-d');

            $extras = collect();

            if (data_get($value, 'service_extras.0')) {
                $extras = ServiceExtra::with([
                    'translation' => fn($query) => $query
                        ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                            $q->where('locale', $this->language)->orWhere('locale', $locale);
                        })),
                ])->find($value['service_extras']);
            }

            $extraPrice = $extras->sum('price') * $rate;

            $shop   = $serviceMaster->shop;
            $master = $serviceMaster->master;

            $items[$key]['service_master'] = ServiceMasterResource::make($serviceMaster);
            $items[$key]['master'] = $master ? UserResource::make($master) : null;
            $items[$key]['shop'] = $shop ? ShopResource::make($shop) : null;
            $items[$key]['user'] = $user ? UserResource::make($user) : null;

            $totalPrice = $serviceMaster->total_price  * $rate;

            $from = $startDate->format('H:i');
            $to   = $endDate->format('H:i');

            if (isset($value['price_id'])) {

                $serviceMasterPrice = ServiceMasterPrice::where('service_master_id', $serviceMaster->id)
                    ->find($value['price_id']);

                $totalPrice = $serviceMasterPrice?->price;

                $smart = collect($serviceMasterPrice?->smart)
                    ->filter(fn($item) => $from >= $item['from'] && $to <= $item['to'])
                    ->first();

                if (!empty($smart)) {

                    $smartValue = (float)$smart['value'];

                    if ($smart['value_type'] === ServiceMasterPrice::PERCENT) {
                        $smartValue = max($totalPrice / 100 * $smartValue, 0);
                    }

                    $totalPrice = $smart['type'] === ServiceMasterPrice::SMART_TYPE_UP
                        ? $totalPrice + $smartValue
                        : $totalPrice - $smartValue;

                }

                $totalPrice = max($totalPrice, 0) * $rate;

            }

            $data['shop_id'] = $serviceMaster->shop_id;
            $totalPrice += $serviceFee + $extraPrice;

            $items[$key]['extras']              = ServiceExtraResource::collection($extras);
            $items[$key]['service_master_id']   = $serviceMaster->id;
            $items[$key]['start_date']          = $startDate->format('Y-m-d H:i');
            $items[$key]['end_date']            = $endDate->format('Y-m-d H:i');
            $items[$key]['price']               = $serviceMaster->price * $rate;
            $items[$key]['discount']            = $serviceMaster->discount * $rate;
            $items[$key]['service_fee']         = $serviceFee;
            $items[$key]['commission_fee']      = $serviceMaster->commission_fee * $rate;
            $items[$key]['extra_price']         = $extraPrice;
            $items[$key]['note']                = $value['note']   ?? '';
            $items[$key]['data']                = $value['data']   ?? [];
            $items[$key]['gender']              = $value['gender'] ?? '';
            $items[$key]['notes']               = $value['notes']  ?? [];
            $items[$key]['user_member_ship_id'] = $value['user_member_ship_id'] ?? null;
            $items[$key]['total_price']         = $totalPrice;

            $this->userMemberShipCalculate($serviceMaster, $data, $key, $items);

            try {

                $value['days'] = $startDate->diff($endDate)->days ?: 1;

                $times = (new MasterRepository)->times($serviceMaster->master_id, [
                    'start_date' => $startDate->format('Y-m-d H:i'),
                    'end_date'   => $endDate->format('Y-m-d H:i'),
                    'service_master_id' => $serviceMaster->id
                ], false);

                $startTimeFormat = $startDate->format('H:i');
                $endTimeFormat   = $endDate->format('H:i');

                $startTimes = $times[$startDateFormat]   ?? [];
                $endTimes   = $times[$value['end_date']] ?? [];

                if (@$startTimes['closed'] || @$endTimes['closed']) {
                    $items[$key]['errors'][] = __('errors.' . ResponseError::MASTER_CLOSED, locale: $this->language);
                }

                $disabledTimes = $startTimes['disabled_times'] ?? [];

                if (count($disabledTimes) === 0) {
                    continue;
                }

                $min = min($disabledTimes);
                $max = max($disabledTimes);

                if ($startTimeFormat === '00:00') {
                    $startTimeFormat = '23:59';
                }

                if ($endTimeFormat === '00:00') {
                    $endTimeFormat = '23:59';
                }

                if (!isset($data['ids']) && $startTimeFormat >= $min && $endTimeFormat <= $max) {
                    $items[$key]['errors'][] = __(
                        'errors.' . ResponseError::ALREADY_BOOKED,
                        ['start_date' => $min, 'end_date' => $max],
                        $this->language
                    );
                }

            } catch (Throwable $e) {
                $items[$key]['errors'][] = $e->getMessage() . $e->getFile() . $e->getLine();
            }

        }

        $status        = true;
        $price         = 0;
        $discount      = 0;
        $totalPrice    = collect($items)->sum('total_price');
        $extraPrice    = collect($items)->sum('extra_price');
        $serviceFee    = 0;
        $couponPrice   = 0;
        $commissionFee = 0;
        $giftPrice     = 0;
        $decrementGiftPrice = 0;
        $message       = '';

        if ($giftCartPrice > 0 && $totalPrice > 0) {
            $giftPrice = $giftCartPrice / count($items);
        }

        foreach (collect($items)->sortBy('total_price')->toArray() as $key => $item) {

            $price         += $item['price']          ?? 0;
            $discount      += $item['discount']       ?? 0;
            $serviceFee    += $item['service_fee']    ?? 0;
            $commissionFee += $item['commission_fee'] ?? 0;

            if (isset($item['errors'])) {
                $status = false;
                $message = @value($item['errors'])[0];
            }

            if ($items[$key]['total_price'] == 0) {
                continue;
            }

            if ($giftCartPrice >= $items[$key]['total_price']) {

                $decrementGiftPrice += $items[$key]['total_price'];

                $items[$key]['gift_cart_price'] = $items[$key]['total_price'];
                $items[$key]['total_price'] = 0;

            } elseif ($giftPrice > 0) {

                $decrementPrice = $giftPrice;

                if ($giftPrice > $item['total_price']) {
                    $giftPrice      = ($giftPrice - $item['total_price']);
                    $decrementPrice = $item['total_price'];
                }

                $items[$key]['gift_cart_price'] = $decrementPrice;
                $items[$key]['total_price']     = $item['total_price'] - $decrementPrice;
                $decrementGiftPrice += $decrementPrice;

            }

        }

        if (isset($data['coupon']) && $totalPrice > 0) {
            $couponPrice = OrderHelper::couponPrice($data, $data['coupon'], $totalPrice, $rate, $data['shop_id']);
            $totalPrice -= $couponPrice;
        }

        $totalPrice -= $decrementGiftPrice;

        return [
            'status'                => $status,
            'message'               => $message,
            'start_date'            => $data['start_date'],
            'user_id'               => $data['user_id'],
            'shop_id'               => $data['shop_id'],
            'end_date'              => $endDate->format('Y-m-d H:i'),
            'user_gift_cart_id'     => $data['user_gift_cart_id'] ?? 0,
            'rate'                  => $rate,
            'price'                 => $price,
            'total_price'           => max($totalPrice, 0),
            'total_extra_price'     => max($extraPrice, 0),
            'coupon_price'          => $couponPrice,
            'total_discount'        => $discount,
            'total_service_fee'     => $serviceFee,
            'total_commission_fee'  => $commissionFee,
            'total_gift_cart_price' => $decrementGiftPrice,
            'items'                 => $items,
        ];
    }

    /**
     * @param ServiceMaster|ServiceMasterResource $serviceMaster
     * @param array $data
     * @param int $key
     * @param array $items
     * @return UserMemberShip|null
     */
    public function userMemberShipCalculate(
        ServiceMaster|ServiceMasterResource $serviceMaster,
        array $data,
        int $key,
        array &$items,
    ): ?UserMemberShip
    {
        $userMemberShip = UserMemberShip::with(['memberShipServices'])
            ->where([
                ['id',         '=', $items[$key]['user_member_ship_id'] ?? null],
                ['user_id',    '=', $data['user_id'] ?? auth('sanctum')->id()],
                ['expired_at', '>', date('Y-m-d H:i:s')],
            ])
            ->first();

        if (empty($userMemberShip)) {
            return null;
        }

        /** @var UserMemberShip $userMemberShip */
        $memberService = $userMemberShip->memberShipServices
            ?->where('service_id', $serviceMaster->service_id)
            ?->first();

        $isLimited = $userMemberShip->sessions === MemberShip::LIMITED && $userMemberShip->remainder > 0;

        if (!empty($memberService) && ($isLimited || $userMemberShip->sessions === MemberShip::UNLIMITED)) {

            $items[$key]['user_member_ship_id'] = $userMemberShip->id;
            $items[$key]['price']               = 0;
            $items[$key]['discount']            = 0;
            $items[$key]['service_fee']         = 0;
            $items[$key]['commission_fee']      = 0;
            $items[$key]['extra_price']         = 0;
            $items[$key]['total_price']         = 0;

        }

        return $userMemberShip;
    }
}
