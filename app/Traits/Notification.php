<?php
declare(strict_types=1);

namespace App\Traits;

use App\Helpers\ResponseError;
use App\Models\Blog;
use App\Models\BlogTranslation;
use App\Models\Booking;
use App\Models\Language;
use App\Models\NotificationUser;
use App\Models\Order;
use App\Models\ParcelOrder;
use App\Models\PushNotification;
use App\Models\Settings;
use App\Models\Translation;
use App\Models\User;
use App\Services\PushNotificationService\PushNotificationService;
use Illuminate\Support\Facades\Http;

trait Notification
{
    private string $url = 'https://fcm.googleapis.com/fcm/send';

    public function sendNotification(
        mixed $model = null,
        array|null $receivers = [],
        string|int|null $message = '',
        string|int|null $title = null,
        mixed $data = [],
        array $userIds = [],
    ): void
    {

        if (is_array($userIds) && count($userIds) > 0 && !empty($model)) {
            (new PushNotificationService)->storeMany([
                'type'  => (string)($data['type'] ?? @$data['order']['type']) ?? PushNotification::NEW_ORDER,
                'title' => $title,
                'body'  => $message,
                'data'  => $data,
            ], $userIds, $model);
        }

        if (empty($receivers)) {
            return;
        }

        $serverKey = $this->firebaseKey();

        $fields = [
            'registration_ids' => $receivers,
            'notification' => [
                'body'  => $message,
                'title' => $title,
                'sound' => 'default',
            ],
            'data' => $data
        ];

        $headers = [
            'Authorization' => "key=$serverKey",
            'Content-Type' => 'application/json'
        ];

// $result =
        Http::withHeaders($headers)->post($this->url, $fields);
//
//        Log::error('fcm', [
//            'count'     => count($receivers),
//            'users'     => count($userIds),
//            'model'     => $model,
//            'message'   => $message,
//            'title'     => $title,
//            'data'      => $data,
//            'userIds'   => $userIds,
//            'res'       => $result->json(),
//        ]);

    }

    public function sendAllNotification(
        Blog $model,
        mixed $data = [],
    ): void
    {
        User::select([
            'id',
            'active',
            'email_verified_at',
            'phone_verified_at',
            'firebase_token',
            'lang',
        ])
            ->where('active', 1)
            ->where(function ($query) {
                $query
                    ->whereNotNull('email_verified_at')
                    ->orWhereNotNull('phone_verified_at');
            })
            ->whereNotNull('firebase_token')
            ->orderBy('id')
            ->chunk(100, function ($users) use ($model, $data) {

                foreach ($users as $user) {
                    /** @var User $user */

                    if (!isset($this->language)) {
                        $locale = Language::where('default', 1)->first()?->locale;

                        $this->language = $locale;
                    }

                    $translation = $model->translations
                        ?->where('locale', $user->lang ?? $this->language)
                        ?->first();

                    if (empty($translation)) {
                        $translation = $model->translations?->first();
                    }

                    /** @var BlogTranslation $translation */
                    $this->sendNotification(
                        $model,
                        $user->firebase_token,
                        $translation?->short_desc,
                        $translation?->title,
                        $data,
                        [$user->id]
                    );
                }

            });
    }

    private function firebaseKey()
    {
        return Settings::where('key', 'server_key')->pluck('value')->first();
    }

    /**
     * @param array $result
     * @param string $class
     * @return void
     */
    public function adminNotify(array $result, string $class = Order::class): void
    {
        $admins = User::whereHas('roles', fn($q) => $q->where('name', 'admin') )
            ->whereNotNull('firebase_token')
            ->select(['id', 'lang', 'firebase_token'])
            ->get();

        if ($class === Order::class) {

            foreach (data_get($result, 'data', []) as $order) {
                $this->sendUsers($order, $admins);
            }

            return;
        }

        $this->sendUsers(data_get($result, 'data'), $admins);
    }

    /**
     * @param Order|ParcelOrder|Booking $model
     * @param $users
     * @return void
     */
    private function sendUsers(Order|ParcelOrder|Booking $model, $users): void
    {
        [$type, $messageKey] = match(get_class($model)) {
            ParcelOrder::class => [PushNotification::NEW_PARCEL_ORDER, ResponseError::NEW_PARCEL_ORDER],
            Booking::class     => [PushNotification::NEW_BOOKING, ResponseError::NEW_BOOKING],
            default            => [PushNotification::NEW_ORDER, ResponseError::NEW_ORDER],
        };

        if (!isset($this->language)) {
            $locale = Language::where('default', 1)->first()?->locale;

            $this->language = $locale;
        }

        foreach ($users as $user) {

            if (empty($user)) {
                continue;
            }

            /** @var User $user */
            $this->sendNotification(
                $model,
                $user->firebase_token ?? [],
                __("errors.$messageKey", ['id' => $model?->id ?? ''], $user->lang ?? $this->language),
                __("errors.$messageKey", ['id' => $model?->id ?? ''], $user->lang ?? $this->language),
                [
                    'id'     => $model->id,
                    'status' => $model->status,
                    'type'   => $type
                ],
                [$user->id]
            );

        }

    }

    /**
     * @param Order|ParcelOrder $order
     * @param bool $isDelivery
     * @return void
     */
    public function statusUpdateNotify(Order|ParcelOrder $order, bool $isDelivery): void
    {
        $order->loadMissing([
            'user.notifications',
            'deliveryman',
        ]);

        $notification = $order->user?->notifications?->where('type', \App\Models\Notification::PUSH)?->first();

        $default = Language::where('default', 1)->first()?->locale;

        if (!isset($this->language)) {
            $this->language = $default;
        }

        /** @var NotificationUser $notification */
        if ($order->user?->id && $notification?->notification?->active) {

            $tStatus = Translation::where(function ($q) use ($order, $default) {
                $q->where('locale', $order->user->lang ?? $this->language)->orWhere('locale', $default);
            })
                ->where('key', $order->status)
                ->first()
                ?->value;

            $title = __(
                'errors.' . ResponseError::STATUS_CHANGED,
                ['status' => !empty($tStatus) ? $tStatus : $order->status, 'id' => $order->id],
                $order->user->lang ?? $this->language
            );

            $this->sendNotification(
                $order,
                $order->user->firebase_token ?? [],
                $title,
                $title,
                [
                    'id'     => $order->id,
                    'status' => $order->status,
                    'type'   => PushNotification::STATUS_CHANGED
                ],
                [$order->user->id]
            );
        }

        if (!$isDelivery && $order->deliveryman?->id) {

            $tStatus = Translation::where(function ($q) use ($order, $default) {
                $q->where('locale', $order->deliveryman->lang ?? $this->language)->orWhere('locale', $default);
            })
                ->where('key', $order->status)
                ->first()
                ?->value;

            $title = __(
                'errors.' . ResponseError::STATUS_CHANGED,
                ['status' => !empty($tStatus) ? $tStatus : $order->status, 'id' => $order->id],
                $order->deliveryman->lang ?? $this->language
            );

            $this->sendNotification(
                $order,
                $order->deliveryman->firebase_token ?? [],
                $title,
                $title,
                [
                    'id'     => $order->id,
                    'status' => $order->status,
                    'type'   => PushNotification::STATUS_CHANGED
                ],
                [$order->deliveryman->id]
            );

        }

    }

    /**
     * @param Booking $booking
     * @return void
     */
    public function bookingStatusUpdateNotify(Booking $booking): void
    {
        $booking->loadMissing([
            'user:id,lang,firebase_token',
            'user.notifications',
            'master:id,lang,firebase_token',
            'shop:id,user_id',
            'shop.seller:id,lang,firebase_token',
        ]);

        $notification = $booking->user?->notifications?->where('type', \App\Models\Notification::PUSH)?->first();

        $default = Language::where('default', 1)->first()?->locale;

        if (!isset($this->language)) {
            $this->language = $default;
        }

        /** @var NotificationUser $notification */
        if (!$booking->user?->id || !$notification?->notification?->active) {
            return;
        }

        $tStatus = Translation::where('key', "booking_$booking->status")
            ->where(fn($q) => $q->where('locale', $booking->user->lang ?? $this->language)->orWhere('locale', $default))
            ->first()
            ?->value;

        $title = __(
            'errors.' . ResponseError::BOOKING_STATUS_CHANGED,
            ['status' => $tStatus ?? $booking->status, 'id' => $booking->id],
            $booking->user->lang ?? $this->language
        );

        $this->sendNotification(
            $booking,
            $booking->user->firebase_token ?? [],
            $title,
            $title,
            [
                'id'     => $booking->id,
                'status' => $booking->status,
                'type'   => PushNotification::BOOKING_STATUS_CHANGED
            ],
            [$booking->user->id]
        );

        $this->sendNotification(
            $booking,
            $booking->master->firebase_token ?? [],
            $title,
            $title,
            [
                'id'     => $booking->id,
                'status' => $booking->status,
                'type'   => PushNotification::STATUS_CHANGED
            ],
            [$booking->master->id]
        );

        $this->sendNotification(
            $booking,
            $booking->shop?->seller?->firebase_token ?? [],
            $title,
            $title,
            [
                'id'     => $booking->id,
                'status' => $booking->status,
                'type'   => PushNotification::STATUS_CHANGED
            ],
            [$booking->shop?->user_id]
        );
    }

    public function bookingUserMaster(array $bookings, $key = 'master'): void
    {
        foreach ($bookings as $booking) {

            /** @var Booking $booking */
            $master = $booking->$key;

            if (empty($master)) {
                continue;
            }

            /** @var User $master */
            $tokens = is_array($master->firebase_token) ? $master->firebase_token : [$master->firebase_token];
            $lang   = $master->lang ?? $this->language;

            $message = __('errors.' . ResponseError::NEW_BOOKING, $this->getReplace($booking), $lang);

            $data = [
                'id'     => $booking->id,
                'price'  => $booking->price,
                'type'   => PushNotification::NEW_BOOKING
            ];

            $this->sendNotification($booking, $tokens, $message, $message, $data, [$master->id]);

        }
    }

    /**
     * @param array $bookings
     * @return void
     */
    public function bookingSeller(array $bookings): void
    {
        foreach ($bookings as $booking) {

            $seller = User::select(['id', 'firebase_token', 'lang'])->find($booking->serviceMaster->shop->user_id);

            if (empty($seller)) {
                continue;
            }

            /** @var User $seller */
            $lang   = $seller->lang ?? $this->language;
            $tokens = $seller->firebase_token;

            $message = __('errors.' . ResponseError::NEW_BOOKING, $this->getReplace($booking), $lang);

            $data = [
                'id'    => $booking->id,
                'price' => $booking->price,
                'type'  => PushNotification::NEW_BOOKING
            ];

            $this->sendNotification($booking, $tokens, $message, $message, $data, [$seller->id]);
        }
    }

    /**
     * @param Booking $booking
     * @return array
     */
    public function getReplace(Booking $booking): array
    {
        $service = $booking->serviceMaster;

        return [
            'id'      => $booking->id,
            'shop'    => $service?->shop?->translation?->title ?? "#$service->shop_id",
            'service' => $service?->service?->translation?->title ?? "#$service->id",
            'master'  => $booking->master?->fullName ?? 'no name',
        ];
    }

    /**
     * @param array $bookings
     * @return void
     */
    public function sendAllBooking(array $bookings): void
    {
        $this->bookingUserMaster($bookings);
        $this->bookingUserMaster($bookings);
        $this->bookingSeller($bookings);
    }

    /**
     * @param Booking $model
     * @param string $key
     * @param array $data
     * @param string|null $text
     * @return void
     */
    public function sendAllUpdateBooking(Booking $model, string $key, array $data, ?string $text = null): void
    {
        //master notify
        [$message, $replace, $firebaseToken, $ids] = $this->getAttributes($model, $model->master_id, $key, $data);

        $this->sendNotification($model, $firebaseToken, "$message:$text", $model->id, $replace, $ids);

        //user notify
        $notification = $model->user?->notifications?->where('type', \App\Models\Notification::PUSH)?->first();

        if ($notification?->notification?->active) {

            [$message, $replace, $firebaseToken, $ids] = $this->getAttributes($model, $model->user_id, $key, $data);

            $this->sendNotification($model, $firebaseToken, $message, $message, $replace, $ids);

        }

        //seller notify
        [$message, $replace, $firebaseToken, $ids] = $this->getAttributes($model, $model->shop?->user_id, $key, $data);

        $this->sendNotification($model, $firebaseToken, $message, $message, $replace, $ids);
    }

    /**
     * @param Booking $model
     * @param int $id
     * @param string $key
     * @param array $data
     * @return array
     */
    public function getAttributes(Booking $model, int $id, string $key, array $data): array
    {
        /** @var User $auth */
        $auth   = auth('sanctum')->user();
        $master = $model->master;
        $user   = $model->user;
        $seller = $model->shop?->seller;
        $ids    = [$master?->id, $user?->id, $seller?->id];

        [$editor, $lang, $firebaseToken] = match($id) {
            (int)$master?->id => [$master?->fullName, $master?->lang ?? $this->language, $master->firebase_token ?? []],
            (int)$user?->id   => [$user?->fullName,   $user?->lang   ?? $this->language, $user->firebase_token   ?? []],
            (int)$seller?->id => [$seller?->fullName, $seller?->lang ?? $this->language, $seller->firebase_token ?? []],
            default           => [$auth->fullName,    $auth->lang    ?? $this->language, $auth->firebase_token   ?? []],
        };

        $replace = [];

        if ($key === ResponseError::BOOKING_ACTIVITY_RESCHEDULE) {
            $replace = [
                'editor'     => $editor,
                'start_date' => $data['start_date'],
                'end_date'   => $data['start_date'],
            ];
        }

        // change by $key
        $message = __("errors.$key", $replace, locale: $lang);

        $replace = [
            'id'    => $model->id,
            'price' => $model->price,
            'type'  => PushNotification::BOOKING_UPDATED
        ];

        return [$message, $replace, $firebaseToken, $ids];
    }
}
