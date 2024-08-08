<?php
declare(strict_types=1);

namespace App\Services\BookingService;

use App\Helpers\ResponseError;
use App\Models\Booking;
use App\Models\BookingActivity;
use App\Models\ServiceMaster;
use App\Models\Translation;
use App\Models\User;
use App\Services\CoreService;
use App\Traits\Notification;

class BookingActivityService extends CoreService
{
    use Notification;

    protected function getModelClass(): string
    {
        return BookingActivity::class;
    }

    /**
     * @param Booking $booking
     * @param string $type
     * @param string|null $locale
     * @param array|null $data
     * @return void
     */
    public function create(Booking $booking, string $type, ?string $locale = 'en', ?array $data = []): void
    {
        /** @var User $user */
        $user = auth('sanctum')->user();
        $note = null;

        switch ($type) {
            case 'new':
            case 'canceled':
            case 'booked':
            case 'progress':
            case 'ended':

                $tStatus = Translation::where('locale', $this->language)
                    ->where('key', "booking_$type")
                    ->first()
                    ?->value;

                $note = __(
                    'errors.' . ResponseError::BOOKING_ACTIVITY_STATUS_CHANGED,
                    ['status' => $tStatus ?? $type],
                    $locale
                );

                break;
            case 'extra_time':

                $note = __('errors.' . ResponseError::BOOKING_ACTIVITY_ADD_EXTRA_TIME, locale: $locale);

                break;
            case 'update':

                if ( // reschedule
                    isset($data['start_date']) && $booking->start_date?->format('Y-m-d H:i') !== $data['start_date'] ||
                    isset($data['end_date'])   && $booking->end_date?->format('Y-m-d H:i')   !== $data['end_date']
                ) {

                    $startDate = $booking->start_date?->format('Y-m-d H:i');
                    $endDate   = $booking->end_date?->format('Y-m-d H:i');
                    $startDate = date('Y-m-d H:i', strtotime($data['start_date'] ?? $startDate));
                    $endDate   = date('Y-m-d H:i', strtotime($data['end_date']   ?? $endDate));

                    $note = __(
                        'errors.' . ResponseError::BOOKING_ACTIVITY_RESCHEDULE,
                        [
                            'editor'     => $user->fullName,
                            'start_date' => $startDate,
                            'end_date'   => $endDate
                        ],
                        $locale
                    );
                } elseif (isset($data['master_id']) && $booking->master_id !== $data['master_id']) { // update_master

                    $master = User::select(['id', 'first_name', 'last_name'])->find($data['master_id']);

                    $note = __(
                        'errors.' . ResponseError::BOOKING_MASTER_UPDATED,
                        ['master' => $master?->fullName],
                        $locale
                    );

                } elseif (
                    isset($data['service_master_id'])
                    && $booking->service_master_id !== $data['service_master_id']
                ) { // update_service

                    /** @var ServiceMaster $from */
                    $from = ServiceMaster::with([
                        'service:id',
                        'service.translation' => fn($q) => $q
                            ->select(['id', 'service_id', 'locale', 'title'])
                            ->where(fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale)),
                    ])->find($booking->service_master_id);

                    /** @var ServiceMaster $to */
                    $to = ServiceMaster::with([
                        'service:id',
                        'service.translation' => fn($q) => $q
                            ->select(['id', 'service_id', 'locale', 'title'])
                            ->where(fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale)),
                    ])->find($data['service_master_id']);

                    $note = __(
                        'errors.' . ResponseError::BOOKING_SERVICE_MASTER_UPDATED,
                        ['from' => $from?->service?->translation?->title, 'to' => $to?->service?->translation?->title],
                        $locale
                    );

                } elseif (isset($data['price']) && $booking->price !== $data['price']) { // update_price

                    $note = __(
                        'errors.' . ResponseError::BOOKING_PRICE_UPDATED,
                        ['from' => $booking->price, 'to' => $data['price']],
                        $locale
                    );

                } elseif (isset($data['notes'])) { // added note

                    $note = __('errors.' . ResponseError::BOOKING_NOTE_UPDATED, locale: $locale);

                } elseif (isset($data['service_extras'])) { // extras note

                    $note = __('errors.' . ResponseError::BOOKING_EXTRAS_UPDATED, locale: $locale);

                }

                break;
        }

        if (!empty($note)) {
            $booking->activities()->create([
                'note'    => $note,
                'user_id' => auth('sanctum')->id(),
                'type'    => $type,
            ]);
        }

    }

}
