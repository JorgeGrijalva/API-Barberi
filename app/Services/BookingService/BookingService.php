<?php
declare(strict_types=1);

namespace App\Services\BookingService;

use App\Helpers\OrderHelper;
use App\Helpers\ResponseError;
use App\Http\Resources\ServiceMasterResource;
use App\Models\Booking;
use App\Models\BookingCoupon;
use App\Models\BookingExtraTime;
use App\Models\Currency;
use App\Models\MemberShip;
use App\Models\ServiceExtra;
use App\Models\ServiceMaster;
use App\Models\Settings;
use App\Models\ShopSubscription;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserGiftCart;
use App\Models\UserMemberShip;
use App\Repositories\BookingRepository\BookingRepository;
use App\Services\CoreService;
use App\Services\TransactionService\TransactionService;
use App\Traits\Notification;
use DateInterval;
use DateTime;
use DB;
use Exception;
use Throwable;

class BookingService extends CoreService
{
    use Notification;

    protected function getModelClass(): string
    {
        return Booking::class;
    }

    /**
     * @param array $data
     * @return array
     */
    public function create(array $data): array
    {
        $calculate = [];

        try {
            $models = DB::transaction(function () use ($data, $calculate) {

                $rate = Currency::currenciesList()->find($this->currency)?->rate ?: 1;

                $calculate = (new BookingRepository)->calculate($data);

                if (!data_get($calculate, 'status')) {
                    throw new Exception(
                        data_get(
                            $calculate,
                            'message',
                            __('errors.' . ResponseError::ERROR_400, locale: $this->language)
                        )
                    );
                }

                $models    = [];
                $parentId  = null;
                $shopId    = $calculate['shop_id'] ?? null;
                $coupon    = null;

                $calculate['total_price'] = $calculate['total_price'] / $calculate['rate'];

                if (isset($data['coupon'])) {
                    $coupon = OrderHelper::couponPrice($data, $data['coupon'], $calculate['total_price'], $calculate['rate'], $shopId);
                }

                if (isset($calculate['total_gift_cart_price']) && isset($data['user_gift_cart_id'])) {
                    $this->updateGiftCartPrice($calculate);
                }

                $items = collect($calculate['items']);

                foreach ($data['data'] as $item) {

                    /** @var ServiceMaster|null $serviceMaster */
                    $calculateValue = $items->where('service_master_id', $item['service_master_id'])->first();
                    $serviceMaster  = @$calculateValue['service_master'];

                    if (empty($calculateValue)) {
                        throw new Exception(__('errors.' . ResponseError::ERROR_400, locale: $this->language));
                    }

                    if (
                        $calculateValue['end_date'] < $calculateValue['start_date']
                        || $calculateValue['start_date'] < now()->format('Y-m-d H:i')
                    ) {
                        throw new Exception(__('errors.' . ResponseError::ERROR_509, locale: $this->language));
                    }

                    $item = $this->beforeSave($item, $rate, $shopId);

                    $couponPrice = $coupon > 0 ? ($coupon / count($data['data']) / $rate) : 0;

                    $item['parent_id']         = $parentId;
                    $item['user_id']           = $data['user_id'];
                    $item['currency_id']       = $this->currency;
                    $item['user_gift_cart_id'] = $data['user_gift_cart_id'] ?? null;
                    $item['start_date']        = $calculateValue['start_date'];
                    $item['end_date']          = $calculateValue['end_date'];
                    $item['coupon_price']      = $couponPrice;
                    $item['extra_price']       = @($calculateValue['extra_price'] / $rate) ?? 0;
                    $item['gift_cart_price']   = @($calculateValue['gift_cart_price'] / $rate) ?? 0;
                    $item['total_price']       = @($calculateValue['total_price'] / $rate) - $couponPrice ?? 0;
                    $item                      = $this->updateMemberShip($serviceMaster, $data, $item);

                    /** @var Booking $model */
                    $model = $this->model()->create($item);

                    if ($couponPrice) {
                        BookingCoupon::create([
                            'name'       => $data['coupon'],
                            'booking_id' => $model->id,
                            'user_id'    => $model->user_id,
                            'price'      => $couponPrice,
                        ]);
                    }

                    if (empty($parentId)) {
                        $parentId = $model->id;
                    }

                    foreach ($calculateValue['extras'] ?? [] as $extra) {
                        $model->extras()->create(['price' => $extra->price, 'service_extra_id' => $extra->id]);
                    }

                    if (data_get($data, 'payment_id') && !data_get($data, 'trx_status')) {

                        $data['payment_sys_id'] = data_get($data, 'payment_id');

                        $transaction = (new TransactionService)
                            ->orderTransaction(
                                $model->id,
                                $data,
                                Booking::class,
                                $data['transaction_status'] ?? Transaction::STATUS_PROGRESS
                            );

                        if (!data_get($transaction, 'status')) {
                            throw new Exception(data_get($transaction, 'message'));
                        }

                    }

                    $model = $model->fresh((new BookingRepository)->getWith());

                    $models[] = $model;

                    $isSubscribe = (int)Settings::where('key', 'by_subscription')->first()?->value;

                    if ($isSubscribe) {

                        /** @var ShopSubscription $subscription */
                        $subscription = ShopSubscription::with(['subscription', 'shop'])
                            ->where('shop_id', $model->shop_id)
                            ->where('expired_at', '>=', now())
                            ->where('active', true)
                            ->first();

                        $shopDemandCount = $model->shop?->b_count;

                        if ($subscription?->subscription?->booking_limit < $shopDemandCount) {
                            $subscription->shop?->update([
                                'visibility' => 0
                            ]);
                        }

                    }

                }

                $this->sendAllBooking($models);

                return $models;
            });

            return [
                'status'  => true,
                'message' => ResponseError::NO_ERROR,
                'data'    => $models,
            ];
        } catch (Throwable $e) {

            $this->error($e);

            return [
                'status'  => false,
                'message' => $e->getMessage(),// . $e->getFile() . $e->getLine()
                'code'    => ResponseError::ERROR_501,
                'data'    => $calculate
            ];
        }
    }

    public function update(Booking $model, array $data): array
    {
        try {
            $model = DB::transaction(function () use ($model, $data) {

                $model->load(['transaction']);

                (new BookingActivityService)->create($model, 'update', $this->language, $data);

                $data['notes'] = array_merge($model->notes ?? [], $data['notes'] ?? []);

                if (isset($data['service_master_id'])) {

                    /** @var ServiceMaster $serviceMaster */
                    $serviceMaster = ServiceMaster::select(['master_id'])->find($data['service_master_id']);

                    $data['master_id'] = $serviceMaster->master_id;

                }

                if (
                    @$data['status'] === Booking::STATUS_ENDED
                    && $model->transaction?->status === Transaction::STATUS_PROGRESS
                ) {
                    $model->transaction?->update(['status' => Transaction::STATUS_PAID]);
                }

                $extras = collect();

                if (data_get($data, 'service_extras.0')) {
                    $extras = ServiceExtra::find($data['service_extras']);
                }

                if ($extras->count() > 0) {

                    $extraPrice = $extras->sum('price');

                    $model->extras()->delete();

                    $oldExtraPrice = $model->extras->sum('price');

                    foreach ($extras as $extra) {
                        $model->extras()->create(['price' => $extra->price, 'service_extra_id' => $extra->id]);
                    }

                    $data['extra_price'] = $extraPrice;
                    $data['total_price'] = $model->extra_price - $oldExtraPrice + $extraPrice;

                }

                $model->update($data);

                $userMemberShip = UserMemberShip::with(['memberShipServices'])
                    ->where([
                        ['user_id',    '=', $data['user_id'] ?? auth('sanctum')->id()],
                        ['id',         '=', $data['user_member_ship_id'] ?? null],
                        ['expired_at', '>', date('Y-m-d H:i:s')],
                    ])
                    ->first();

                /** @var UserMemberShip $userMemberShip */
                if ($userMemberShip?->sessions === MemberShip::LIMITED && isset($data['user_member_ship_id'])) {
                    $userMemberShip->decrement('remainder');
                }

                if (!empty($model->user_member_ship_id)) {
                    UserMemberShip::where('sessions', MemberShip::LIMITED)
                        ->find($model->user_member_ship_id)
                        ?->increment('remainder');
                }

                $moveTheNeXT = Settings::where('key', 'can_move_the_reservation_time')->first()?->value;

                if (@$data['next_times_update'] && $moveTheNeXT) {
                    $this->nextTimeBookingsUpdate($model, ResponseError::BOOKING_ACTIVITY_RESCHEDULE, $data);
                }

                $this->sendAllUpdateBooking($model, ResponseError::BOOKING_UPDATED, $data);

                return $model;
            });

            return [
                'status'  => true,
                'message' => ResponseError::NO_ERROR,
                'data'    => $model->fresh((new BookingRepository)->getWith()),
            ];
        } catch (Throwable $e) {

            $this->error($e);

            return ['status' => false, 'message' => $e->getMessage() . $e->getFile() . $e->getLine(), 'code' => ResponseError::ERROR_502];
        }
    }

    /**
     * @param int $id
     * @param array $filter
     * @return Booking
     * @throws Throwable
     */
    public function statusUpdate(int $id, array $filter): Booking
    {
        $status = $filter['status'];

        $model = Booking::with(['shop', 'master', 'user'])
            ->has('master')
            ->has('user')
            ->has('shop')
            ->find($id);

        if (empty($model)) {
            throw new Exception(__('errors.' . ResponseError::ERROR_404, locale: $this->language));
        }

        /** @var Booking $model */
        if ($model->status === $status) {
            throw new Exception(__('errors.' . ResponseError::ERROR_252, locale: $this->language));
        }

        $this->checkAssignedBeforeUpdate($model);

        $locale = auth('sanctum')->user()->lang ?? $this->language;

        return DB::transaction(function () use ($model, $filter, $status, $locale) {
            (new BookingActivityService)->create($model, $status, $locale);

            //IF before update status was ended we cancel added statistic
            if ($model->status === Booking::STATUS_ENDED && $status === Booking::STATUS_CANCELED) {
                $this->updateStat($model, false);
            }

            if ($status === Booking::STATUS_ENDED) {
                $this->updateStat($model);
            }

            $refundHour = Settings::where('key', 'booking_refund_canceled_hour')->first()?->value ?: 24;
            $canceledCommission = Settings::where('key', 'booking_canceled_commission')->first()?->value;

            if ($status === Booking::STATUS_CANCELED) {

                $totalPrice = $model->total_price;

                if ($model->start_date > date('Y-m-d H:i:s', strtotime("-$refundHour hours"))) {
                    $totalPrice = $totalPrice / 100 * $canceledCommission;
                }

                $model->user->wallet()->decrement('price', $totalPrice);
            }

            $model->update([
                'status' => $status,
                'canceled_note' => $filter['canceled_note'] ?? $model->canceled_note
            ]);

            return $model;
        });
    }

    /**
     * @param int $id
     * @param array $data
     * @return mixed
     * @throws Throwable
     */
    public function canceledByParent(int $id, array $data): mixed
    {
        return DB::transaction(function () use ($id, $data) {

            $model = Booking::with(['user.wallet', 'children'])->where('user_id', auth('sanctum')->id())->find($id);

            if (empty($model)) {
                throw new Exception(__('errors.' . ResponseError::ERROR_404, locale: $this->language));
            }

            /** @var Booking $model */
            $model->update($data);

            $model->children()->update($data);

            (new BookingActivityService)->create($model, $data['status'], $this->language, $data);

            $refundHour = Settings::where('key', 'booking_refund_canceled_hour')->first()?->value ?: 24;
            $canceledCommission = Settings::where('key', 'booking_canceled_commission')->first()?->value;

            $totalPrice = $model->total_price + $model->children?->sum('total_price');

            if ($model->start_date > date('Y-m-d H:i:s', strtotime("-$refundHour hours"))) {
                $totalPrice = $totalPrice / 100 * $canceledCommission;
            }

            $model->user->wallet()->decrement('price', $totalPrice);

            return $model;
        });
    }

    /**
     * @throws Exception
     */
    public function beforeSave(array $data, null|float|int $rate = null, ?int &$shopId = null): array
    {
        $serviceMaster = ServiceMaster::where(['active' => true, 'id' => $data['service_master_id']])->first();

        if (empty($serviceMaster) || !$serviceMaster->master_id) {
            throw new Exception(__('errors.' . ResponseError::NO_AVAILABLE_MASTERS, locale: $this->language));
        }

        if (empty($shopId)) {
            $shopId = $serviceMaster->shop_id;
        }

        if ($serviceMaster->shop_id !== $shopId) {
            throw new Exception(__('errors.' . ResponseError::OTHER_SHOP, locale: $this->language));
        }

        $data['master_id']      = $serviceMaster->master_id;
        $data['type']           = $serviceMaster->type;
        $data['discount']       = max((int)$serviceMaster->discount, 0);
        $data['commission_fee'] = max($serviceMaster->commission_fee, 0);
        $data['price']          = max($serviceMaster->price, 0);
        $data['service_fee']    = max((double)Settings::where('key', 'booking_service_fee')->first()?->value, 0);
        $data['rate']           = $rate;
        $data['shop_id']        = $shopId;

        return $data;
    }

    public function delete(?array $ids = [], array $filter = []): void
    {
        $models = Booking::filter($filter)->find(is_array($ids) ? $ids : []);

        foreach ($models as $model) {
            $model->delete();
        }

        try {
            DB::table('push_notifications')
                ->where('model_type', Booking::class)
                ->whereIn('model_id', $models->pluck('id')->toArray())
                ->delete();
        } catch (Throwable $e) {
            $this->error($e);
        }
    }

    /**
     * @param ServiceMaster|ServiceMasterResource $serviceMaster
     * @param array $data
     * @param array $item
     * @return array
     */
    private function updateMemberShip(ServiceMaster|ServiceMasterResource $serviceMaster, array $data, array $item): array
    {
        $item = [$item];

        $userMemberShip = (new BookingRepository)->userMemberShipCalculate($serviceMaster, $data, 0, $item);

        if (empty($userMemberShip)) {
            return $item[0];
        }

        if ($userMemberShip->sessions === MemberShip::LIMITED && $userMemberShip->remainder > 1) {

            $userMemberShip->update(['remainder' => $userMemberShip->remainder - 1]);

            return $item[0];
        }

        if ($userMemberShip->sessions === MemberShip::LIMITED && $userMemberShip->remainder === 0) {
            $userMemberShip->delete();
        }

        return $item[0];
    }

    /**
     * @param int $id
     * @param array $data
     * @return Booking
     * @throws Throwable
     */
    public function notesUpdate(int $id, array $data): Booking
    {
        $model = Booking::with([
            'shop:id,user_id',
            'shop.seller:id,lang,firstname,lastname,firebase_token',
            'master:id,lang,firstname,lastname,firebase_token',
            'user:id,lang,firstname,lastname,firebase_token',
            'user.notifications',
        ])->find($id);

        if (empty($model)) {
            throw new Exception(__('errors.' . ResponseError::ERROR_404, locale: $this->language));
        }

        /** @var Booking $model */
        $this->checkAssignedBeforeUpdate($model);

        return DB::transaction(function () use ($model, $data) {

            (new BookingActivityService)->create($model, 'update', $this->language, ['notes' => $data['note']]);

            $notes   = $model->notes ?? [];
            $notes[] = $data['note'];

            $model->update(['notes' => $notes]);

            $this->sendAllUpdateBooking($model, ResponseError::BOOKING_NOTE_UPDATED, $data, $data['note']);

            return $model;
        });
    }

    /**
     * @param int $id
     * @param array $data
     * @return Booking
     * @throws Throwable
     */
    public function timesUpdate(int $id, array $data): Booking
    {
        $model = Booking::with([
            'shop:id,user_id',
            'shop.seller:id,lang,firstname,lastname,firebase_token',
            'master:id,lang,firstname,lastname,firebase_token',
            'user:id,lang,firstname,lastname,firebase_token',
            'user.notifications',
        ])->find($id);

        if (empty($model)) {
            throw new Exception(__('errors.' . ResponseError::ERROR_404, locale: $this->language));
        }

        /** @var Booking $model */
        $this->checkAssignedBeforeUpdate($model);

        return DB::transaction(function () use ($model, $data) {

            (new BookingActivityService)->create($model, 'update', $this->language, $data);

            if ($data['start_date'] < date('Y-m-d H:i')) {
                throw new Exception(__('errors.' . ResponseError::ERROR_509, locale: $this->language));
            }

            $model->update($data);

            $key = ResponseError::BOOKING_ACTIVITY_RESCHEDULE;

            $this->sendAllUpdateBooking($model, $key, $data);

            $moveTheNeXT = Settings::where('key', 'can_move_the_reservation_time')->first()?->value;

            if ($data['next_times_update'] && $moveTheNeXT) {
                $this->nextTimeBookingsUpdate($model, $key, $data);
            }

            return $model;
        });
    }

    /**
     * @param int $id
     * @param array $data
     * @return Booking
     * @throws Throwable
     */
    public function extraTime(int $id, array $data): Booking
    {
        $model = Booking::with([
            'shop:id,user_id',
            'extraTimes',
            'user:id,lang,firstname,lastname,firebase_token',
            'user.notifications',
        ])->find($id);

        if (empty($model)) {
            throw new Exception(__('errors.' . ResponseError::ERROR_404, locale: $this->language));
        }

        /** @var Booking $model */
        $this->checkAssignedBeforeUpdate($model);

        return DB::transaction(function () use ($model, $data) {

            (new BookingActivityService)->create($model, 'extra_time', $this->language, $data);

//            if (isset($data['remove_ids'][0])) {
//                $model->extraTimes()->whereIn('id', $data['remove_ids'])->delete();
//            }
//
//            if (isset($data['id'])) {
//
//                unset($data['remove_ids']);
//
//                /** @var BookingExtraTime $extraTime */
//                $extraTime = $model->extraTimes()->where('id', $data['id'])->first();
//
//                $unit = "$extraTime->duration $extraTime->duration_type";
//
//                $model->update([
//                    'end_date' => $model->end_date->sub($unit)->format('Y-m-d H:i:s')
//                ]);
//
//                $extraTime->update($data);
//
//                $unit = "$extraTime->duration $extraTime->duration_type";
//
//                $model->update([
//                    'end_date' => $model->end_date->add($unit)->format('Y-m-d H:i:s')
//                ]);
//
//                return $model;
//            }

            /** @var BookingExtraTime $extraTime */
            $extraTime = $model->extraTimes()->create($data);

            $unit = "+$extraTime->duration $extraTime->duration_type";

            $model->update([
                'end_date' => $model->end_date->add($unit)->format('Y-m-d H:i:s')
            ]);

            return $model->fresh(['extraTimes']);
        });
    }

    /**
     * @param Booking $model
     * @param string $key
     * @param array $data
     * @param DateTime|null $startDate
     * @param DateTime|null $endDate
     * @return array
     * @throws Exception
     */
    public function nextTimeBookingsUpdate(
        Booking $model,
        string $key,
        array $data,
        ?DateTime &$startDate = null,
        ?DateTime &$endDate = null
    ): array
    {

        $nextTimeBookings = Booking::with([
            'serviceMaster:id,pause,interval',
            'shop:id,user_id',
            'shop.seller:id,lang,firstname,lastname,firebase_token',
            'master:id,lang,firstname,lastname,firebase_token',
            'user:id,lang,firstname,lastname,firebase_token',
            'user.notifications',
        ])
            ->where('master_id', $model->master_id)
            ->where('id', '!=', $model->id)
            ->where('start_date', '>=', $model->start_date)
            ->where('start_date', '<=', $model->end_date)
            ->orderBy('start_date')
            ->get();

        $times = [];

        foreach ($nextTimeBookings as $booking)  {

            /** @var Booking $booking */
            $serviceMaster = $booking->serviceMaster;

            $pause = $serviceMaster?->pause ?? 15;
            $time  = $serviceMaster?->interval + $pause;

            if (empty($startDate) || empty($endDate)) {
                $startDate = new DateTime($model->end_date?->format('Y-m-d H:i:s'));
                $startDate->add(new DateInterval("PT{$pause}M"));
                $endDate = clone $startDate;
            }

            $endDate = clone $endDate->add(new DateInterval("PT{$time}M"));

            $times[] = [
                $booking->id,
                $startDate->format('Y-m-d H:i'),
                $endDate?->format('Y-m-d H:i'),
                $pause,
                $time
            ];

            $booking->update(['start_date' => $startDate, 'end_date' => $endDate]);

            $this->sendAllUpdateBooking($booking, $key, $data);

            $startDate = clone $endDate->add(new DateInterval("PT{$pause}M"));

            $exist = DB::table('bookings')
                ->whereIn('master_id', $nextTimeBookings->pluck('master_id')->merge([$model->master_id])->unique()->toArray())
                ->whereNotIn('id',     $nextTimeBookings->pluck('id')->merge([$model->id])->unique()->toArray())
                ->where('start_date', '>=', $booking->start_date)
                ->where('end_date', '<=', $booking->end_date)
                ->exists();

            if ($exist) {
                $times[] = $this->nextTimeBookingsUpdate($booking, $key, $data, $startDate, $endDate);
            }

        }

        return $times;
    }

    /**
     * @param Booking $booking
     * @param $data
     * @return array
     */
    public function addReview(Booking $booking, $data): array
    {
        $booking->addAssignReview($data, $booking->master);

        return [
            'status' => true,
            'code'   => ResponseError::NO_ERROR,
            'data'   => $booking
        ];
    }

    /**
     * @param Booking $model
     * @param bool $isIncrement
     * @return void
     */
    public function updateStat(Booking $model, bool $isIncrement = true): void
    {
        $serviceCount = $model->shop->b_count;
        $serviceSum   = $model->shop->b_sum;

        $model->shop->update([
            'b_count' => $isIncrement ? $serviceCount + 1 : $serviceCount - 1,
            'b_sum'   => $isIncrement ? $serviceSum + $model->total_price : $serviceSum - $model->total_price,
        ]);

        $masterCount = $model->master->b_count;
        $masterSum   = $model->master->b_sum;

        $model->master->update([
            'b_count' => $isIncrement ? $masterCount + 1 : $masterCount - 1,
            'b_sum'   => $isIncrement ? $masterSum + $model->total_price : $masterSum - $model->total_price
        ]);

        $userCount = $model->user->b_count;
        $userSum   = $model->user->b_sum;

        $model->user->update([
            'b_count' => $isIncrement ? $userCount + 1 : $userCount - 1,
            'b_sum'   => $isIncrement ? $userSum + $model->total_price : $userSum - $model->total_price
        ]);
    }

    /**
     * @param array $data
     * @return float
     */
    private function updateGiftCartPrice(array $data): float
    {
        $giftCart = UserGiftCart::where('user_id', $data['user_id'])
            ->where('id', $data['user_gift_cart_id'])
            ->first();

        $data['total_gift_cart_price'] = $data['total_gift_cart_price'] / $data['rate'];

        $giftPrice = $giftCart->price - $data['total_gift_cart_price'];

        if ($giftPrice < 0) {
            $giftPrice = $data['total_gift_cart_price'] - $giftCart->price;
        }

        $giftCart?->update(['price' => $giftPrice]);

        if ($giftCart?->price <= 0) {
            $giftCart?->delete();
        }

        return (double)$giftPrice;
    }

    /**
     * @param Booking $model
     * @return void
     * @throws Exception
     */
    public function checkAssignedBeforeUpdate(Booking $model): void
    {
        /** @var User $user */
        $user = auth('sanctum')->user();

        if ($user->hasRole('admin')) {
            return;
        }

        if ($user->hasRole('master') && $model->master_id !== $user->id && $model->user_id !== $user->id) {

            throw new Exception(__('errors.' . ResponseError::ERROR_404, locale: $this->language));

        } else if ($user->hasRole('seller') && $model->shop?->user_id !== $user->id && $model->user_id !== $user->id) {

            throw new Exception(__('errors.' . ResponseError::ERROR_404, locale: $this->language));

        } else if ($user->hasRole('user') && $model->user_id !== $user->id) {

            throw new Exception(__('errors.' . ResponseError::ERROR_404, locale: $this->language));
        }
    }
}
