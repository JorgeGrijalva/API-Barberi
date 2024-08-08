<?php
declare(strict_types=1);

namespace App\Repositories\UserRepository;

use App\Helpers\ResponseError;
use App\Models\Booking;
use App\Models\Invitation;
use App\Models\Language;
use App\Models\MasterDisabledTime;
use App\Models\ServiceMaster;
use App\Models\Settings;
use App\Models\User;
use App\Models\UserWorkingDay;
use App\Repositories\CoreRepository;
use DateInterval;
use DateTime;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MasterRepository extends CoreRepository
{

    protected function getModelClass(): string
    {
        return User::class;
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function index(array $filter = []): LengthAwarePaginator
    {
        $locale = Language::where('default', 1)->first()?->locale;

        return User::filter(array_merge($filter, ['role' => 'master']))
            ->whereHas('serviceMaster', fn($q) => $q
                ->select('service_id', 'active', 'master_id')
                ->where('active', true)
                ->when(data_get($filter, 'service_id'), fn ($query, $id) => $query->where('service_id', $id))
                ->when(data_get($filter, 'service_ids'), fn ($query, $ids) => $query->whereIn('service_id', $ids))
            )
            ->whereHas('invite', fn($q) => $q->select(['user_id', 'status'])->where('status', Invitation::ACCEPTED))
            ->with([
                'invite' => fn($q) => $q
                    ->select(['id', 'user_id', 'shop_id', 'status'])
                    ->where('status', Invitation::ACCEPTED),
                'invite.shop:id,uuid,slug,latitude,longitude',
                'invite.shop.translation' => fn($query) => $query
                    ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                        $q->where('locale', $this->language)->orWhere('locale', $locale);
                    })),
                'translation' => fn($q) => $q
                    ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                        $q->where('locale', $this->language)->orWhere('locale', $locale);
                    })),
                'translations',
                'serviceMaster' => fn($q) => $q
                    ->where('active', true)
                    ->when(data_get($filter, 'service_id'), fn ($query, $id) => $query->where('service_id', $id))
                    ->when(data_get($filter, 'service_ids'), fn ($query, $ids) => $query->whereIn('service_id', $ids)),
                'serviceMaster.service:id',
                'serviceMaster.service.translation' => fn($q) => $q
                    ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                        $q->where('locale', $this->language)->orWhere('locale', $locale);
                    })),
            ])
            ->withMin('serviceMasters', 'price')
            ->paginate($filter['perPage'] ?? 10);
    }

    /**
     * @param User $user
     * @return User
     */
    public function show(User $user): User
    {
        $locale = Language::where('default', 1)->first()?->locale;

        return $user
            ->loadMin('serviceMasters', 'price')
            ->loadMissing([
                'invite' => fn($q) => $q
                    ->select(['user_id', 'shop_id', 'status'])
                    ->where('status', Invitation::ACCEPTED),
                'invite.shop:id,uuid,latitude,longitude',
                'invite.shop.translation' => fn($query) => $query
                    ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                        $q->where('locale', $this->language)->orWhere('locale', $locale);
                    })),
                'translation' => fn($q) => $q
                    ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                        $q->where('locale', $this->language)->orWhere('locale', $locale);
                    })),
                'serviceMasters' => fn($q) => $q->where('active', true),
                'serviceMasters.service:id,slug,category_id',
                'serviceMasters.service.translation'=> fn($q) => $q
                    ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                        $q->where('locale', $this->language)->orWhere('locale', $locale);
                    })),
                'serviceMasters.extras.translation' => fn($q) => $q
                    ->when($this->language, function ($q) use ($locale) {
                        $q->where(fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale));
                    }),
            ]);
    }

    /**
     * @param int $id
     * @param array $filter
     * @param bool $values
     * @return array
     * @throws Exception
     */
    public function times(int $id, array $filter, bool $values = true): array
    {
        $maxDay = (int)(Settings::where('key', 'max_day_booking')->first()?->value ?: 90);

        $now  = date('Y-m-d', strtotime($filter['start_date'] ?? now()->format('Y-m-d')));
        $date = date('Y-m-d', strtotime($filter['end_date']   ?? $now));

        $skipDays = (new DateTime($now))->diff(new DateTime($date))->days;

        if ($skipDays > $maxDay) {
            $skipDays = $maxDay;
        } else if (!isset($filter['end_date'])) {
            $skipDays = $maxDay;
            $date = (new DateTime($now))->add(new DateInterval("P{$skipDays}D"));
        }

        $timeFrom = date('H:i:00', strtotime($filter['start_date'] ?? '00:01:00'));
        $timeTo   = date('H:i:00', strtotime($filter['end_date']   ?? '23:59:00'));

        $serviceMaster = ServiceMaster::find($filter['service_master_id'] ?? null);

        if (empty($serviceMaster) || $serviceMaster->master_id !== $id) {
            throw new Exception(__('errors.' . ResponseError::ERROR_400, locale: $this->language));
        }

        $skipMinute = (int)($serviceMaster->interval + $serviceMaster->pause);

        $master = User::with([

            'workingDays',

            'closedDates' => fn($q) => $q
                ->whereNotNull('date')
                ->whereDate('date', '>=', $now)
                ->whereDate('date', '<=', $date),

            'disabledTimes' => fn($q) => $q
                ->whereNotNull('from')
                ->whereNotNull('to')
                ->whereTime('from', '>=', $timeFrom)
                ->whereTime('to', '<=', $timeTo)
                ->where('can_booking', false),

            'masterBookings' => fn($q) => $q
                ->where('service_master_id', $filter['service_master_id'])
                ->whereNotNull('start_date')
                ->whereNotNull('end_date')
                ->whereIn('status', [Booking::STATUS_NEW, Booking::STATUS_BOOKED, Booking::STATUS_PROGRESS])
                ->whereDate('start_date', '>=', $now)
                ->whereDate('end_date', '<=', $date),
        ])
            ->has('workingDays')
            ->whereHas('roles', fn($q) => $q->where('name', 'master'))
            ->find($id);

        if (empty($master)) {
            throw new Exception(__('errors.' . ResponseError::ERROR_404, locale: $this->language));
        }

        $data = [];

        /** @var User $master */

        for ($i = 0; $skipDays >= $i; $i++) {

            $nextDay = date('Y-m-d', strtotime("$now +$i days"));
            $day = Str::lower(date('l', strtotime($nextDay)));

            $workingDay = $master->workingDays->where('day', $day)->first();

            /** @var UserWorkingDay|null $workingDay */
            $closedDates = $master->closedDates;

            if (!$workingDay || $workingDay->disabled || $closedDates?->where('date', $nextDay)?->isNotEmpty()) {

                $data[$nextDay] = [
                    'date'           => $nextDay,
                    'month'          => date('m', strtotime($nextDay)),
                    'day'            => date('d', strtotime($nextDay)),
                    'name'           => $day,
                    'closed'         => true,
                    'times'          => [],
                    'disabled_times' => [],
                ];

                continue;
            }

            $startTime = new DateTime($workingDay->from);
            $endTime   = new DateTime($workingDay->to);

            $times = $this->collectTimes($startTime, $endTime, $skipMinute);

            $data[$nextDay] = [
                'date'           => $nextDay,
                'month'          => date('m', strtotime($nextDay)),
                'day'            => date('d', strtotime($nextDay)),
                'name'           => $day,
                'closed'         => false,
                'times'          => array_values($times),
                'disabled_times' => [],
            ];

        }

        if ($master->disabledTimes?->count() > 0) {

            $repeatTypes = MasterDisabledTime::REPEATS;
            unset($repeatTypes[MasterDisabledTime::DONT_REPEAT]);

            foreach ($master->disabledTimes as $disabledTime) {

                $disabledTimeFormat = $disabledTime->date;

                $getTime = $data[$disabledTimeFormat] ?? null;

                if ($getTime && $getTime['closed'] === true) {
                    continue;
                }

                $startTime = new DateTime($disabledTime->from);
                $endTime   = new DateTime($disabledTime->to);

                $created = false;

                if (in_array($disabledTime->repeats, $repeatTypes)) {

                    $disabledTimes = $this->collectTimes($startTime, $endTime, $skipMinute);

                    $isCustom      = $disabledTime->repeats === MasterDisabledTime::CUSTOM;
                    $isCustomDay   = $isCustom && $disabledTime->custom_repeat_type === MasterDisabledTime::DAY;
                    $isCustomWeek  = $isCustom && $disabledTime->custom_repeat_type === MasterDisabledTime::WEEK;
                    $isCustomMonth = $isCustom && $disabledTime->custom_repeat_type === MasterDisabledTime::MONTH;
                    $endTypeIsDate = $disabledTime->end_type === MasterDisabledTime::DATE;

                    if (in_array($disabledTime->end_type, [MasterDisabledTime::NEVER, MasterDisabledTime::DATE])) {

                        if ($disabledTime->repeats === MasterDisabledTime::DAY || ($isCustomDay)) {

                            foreach (collect($data)->values()->toArray() as $key => $value) {

                                if ($endTypeIsDate && $disabledTime->end_value < $value['date']) {
                                    continue;
                                }

                                if ($isCustomDay && ($key % @$disabledTime->custom_repeat_value[0] !== 0)) {
                                    continue;
                                }

                                $data[$value['date']] = $this->mergeTimes($data[$value['date']], $disabledTimes);
                            }

                        } elseif ($disabledTime->repeats === MasterDisabledTime::WEEK || ($isCustomWeek)) {

                            $name = Str::lower((new DateTime($disabledTime->date))->format('l'));

                            $newData = collect($data)
                                ->when(
                                    $isCustomWeek,
                                    fn($q) => $q->whereIn('name', $disabledTime->custom_repeat_value),
                                    fn($q) => $q->where('name', $name)
                                )
                                ->toArray();

                            foreach ($newData as $value) {

                                if ($endTypeIsDate && $disabledTime->end_value < $value['date']) {
                                    continue;
                                }

                                $data[$value['date']] = $this->mergeTimes($data[$value['date']], $disabledTimes);
                            }

                        } elseif ($disabledTime->repeats === MasterDisabledTime::MONTH || ($isCustomMonth)) {

                            $day = Str::lower((new DateTime($disabledTime->date))->format('d'));

                            $date = new DateTime($disabledTime->date);
                            $dateFormat = $date->format('Y-m-d');

                            $months = 12 * ($skipDays / 365);

                            if ($disabledTime->end_type === MasterDisabledTime::NEVER && isset($data[$dateFormat])) {
                                $data[$dateFormat] = $this->mergeTimes($data[$dateFormat], $disabledTimes);
                                continue;
                            }

                            $addMonth = $disabledTime->custom_repeat_value[0] ?? 1;

                            for ($i = 1; $i <= (int)round($months); $i += $addMonth) {

                                $modified = false;

                                $lastDayOfMonth = (new DateTime($disabledTime->date))
                                    ->modify('last day of this month')
                                    ->format('d');

                                if ($day === $lastDayOfMonth || $day > 28) {

                                    $date->modify("first day of +$addMonth month");

                                    $lastDay = (clone $date)
                                        ->modify('last day of this month')
                                        ->format('d');

                                    $addDays = $day - ($day - $lastDay) - 1; // for february

                                    if ($lastDay === $day) { // for 30

                                        $addDays = $day - 1;

                                    } elseif ($lastDay > $day) { // for 31

                                        $addDays = $day - ($lastDay - $day) + 1;

                                    }

                                    $date->modify("+$addDays days");
                                    $modified = true;
                                }

                                $dateFormat = $date->format('Y-m-d');

                                if (
                                    isset($data[$dateFormat])
                                    && $endTypeIsDate
                                    && $disabledTime->end_value >= $dateFormat
                                ) {
                                    $data[$dateFormat] = $this->mergeTimes($data[$dateFormat], $disabledTimes);
                                }

                                if (!$modified) {
                                    $date->modify("+$addMonth month");
                                }

                            }

                        }

                    } elseif ($disabledTime->end_type === MasterDisabledTime::AFTER) {

                        $formatDisabledTime = new DateTime($disabledTimeFormat);
                        $days = (int)$disabledTime->end_value;

                        if ($days > $maxDay) {
                            $days = $maxDay;
                        }

                        if ($disabledTime->repeats === MasterDisabledTime::DAY || ($isCustomDay)) {

                            $data = $this->collectAfterDates(
                                $data, $days, 'D', $formatDisabledTime,
                                $startTime, $endTime, $skipMinute, $disabledTime
                            );

                        } elseif ($disabledTime->repeats === MasterDisabledTime::WEEK || ($isCustomWeek)) {

                            $data = $this->collectAfterDates(
                                $data, $days, 'W', $formatDisabledTime,
                                $startTime, $endTime, $skipMinute, $disabledTime
                            );

                        } elseif ($disabledTime->repeats === MasterDisabledTime::MONTH || ($isCustomMonth)) {

                            $data = $this->collectAfterDates(
                                $data, $days, 'M', $formatDisabledTime,
                                $startTime, $endTime, $skipMinute, $disabledTime
                            );

                        }

                        $created = true;
                    }

                    if (!$created && isset($data[$disabledTimeFormat]['disabled_times'][0])) {

                        $times         = $data[$disabledTimeFormat]['times'];
                        $disabledTimes = $data[$disabledTimeFormat]['disabled_times'];

                        $startTime = new DateTime(key($disabledTimes));
                        $endTime   = new DateTime(end($disabledTimes));

                        $data[$disabledTimeFormat]['times'] = $this->removeBookedTimes($times, $startTime, $endTime);

                    }

                } elseif ($disabledTime->repeats === MasterDisabledTime::DONT_REPEAT && isset($data[$disabledTimeFormat])) {

                    $disabledTimes = $this->collectTimes($startTime, $endTime, $skipMinute);

                    $data[$disabledTimeFormat] = $this->mergeTimes($data[$disabledTimeFormat], $disabledTimes);

                }

            }

        }

        $data = $this->collectBookingDays($data, $master, $serviceMaster);

        return $values ? collect($data)->sort()->values()->toArray() : $data;
    }

    /**
     * @param array $filter
     * @return array
     * @throws Exception
     */
    public function timesAll(array $filter): array
    {
        $data = [];

        $serviceMasters = DB::table('service_masters')
            ->select(['master_id', 'id'])
            ->whereIn('id', $filter['service_master_ids'])
            ->get();

        foreach ($serviceMasters as $serviceMaster) {

            /** @var ServiceMaster $serviceMaster */
            $filter['service_master_id'] = $serviceMaster->id;

            $times = $this->times($serviceMaster->master_id, $filter, false);

            if (count($data) === 0) { // for first iteration
                $data = $times;
                continue;
            }

            foreach ($times as $time) {

                if (!isset($data[$time['date']]) || $time['closed']) {
                    $data[$time['date']] = $time;
                    continue;
                }

                if ($data[$time['date']]['closed']) {
                    continue;
                }

                $disabledTimes = collect($time['disabled_times'])
                    ->merge($data[$time['date']]['disabled_times'])
                    ->sort()
                    ->unique()
                    ->values();

                $min = $disabledTimes->min();
                $max = $disabledTimes->max();

                $times = collect($time['times'])
                    ->merge($data[$time['date']]['times'])
                    ->sort()
                    ->filter(fn($hour) => $hour < $min || $hour > $max)
                    ->unique()
                    ->values()
                    ->toArray();

                $data[$time['date']]['disabled_times'] = $disabledTimes->toArray();
                $data[$time['date']]['times']          = $times;
            }

        }

        return array_values($data);
    }

    /**
     * @param array $data
     * @param User $master
     * @param ServiceMaster $serviceMaster
     * @return array
     * @throws Exception
     */
    private function collectBookingDays(array $data, User $master, ServiceMaster $serviceMaster): array
    {
        if ($master->masterBookings?->count() === 0) {
            return $data;
        }

        foreach ($master->masterBookings as $masterBooking) {

            [$startDate, $startTime] = explode(' ', $masterBooking->start_date?->format('Y-m-d H:i'));
            [$endDate,   $endTime]   = explode(' ', $masterBooking->end_date?->format('Y-m-d H:i'));

            $startTime = new DateTime($startTime);
            $endTime   = new DateTime($endTime);
            $skipMinute = $serviceMaster->interval + $serviceMaster->pause;

            $disabledTimes = $this->collectTimes($startTime, $endTime, $skipMinute);
            $disabledTimes = array_merge(
                $data[$startDate]['disabled_times'],
                $this->collectTimes($startTime, $endTime, $skipMinute, false),
                $disabledTimes
            );

            if (isset($data[$startDate])) {
                $data[$startDate] = $this->mergeTimes($data[$startDate], $disabledTimes);
                $disabledTimes    = array_merge($data[$startDate]['disabled_times'], $disabledTimes);
            }

            if (isset($data[$endDate])) {
                $data[$endDate] = $this->mergeTimes($data[$endDate], $disabledTimes);
            }

        }

        return $data;
    }
    /**
     * @param array $data
     * @param int $days
     * @param string $type
     * @param DateTime $formatDisabledTime
     * @param DateTime $startTime
     * @param DateTime $endTime
     * @param int $skipMinute
     * @param MasterDisabledTime $disabledTime
     * @return array
     * @throws Exception
     */
    private function collectAfterDates(
        array $data,
        int $days,
        string $type,
        DateTime $formatDisabledTime,
        DateTime $startTime,
        DateTime $endTime,
        int $skipMinute,
        MasterDisabledTime $disabledTime,
    ): array
    {
        $subDay = 0;

        for ($i = 0; !empty($disabledTime->custom_repeat_value) ? $days > $i : $days >= $i; $i++) {

            $day = $disabledTime->custom_repeat_value[0] ?? 1;

            $weekDays = [];

            if ($i > 0) {

                $dayNumber = (int)$formatDisabledTime->format('d');
                $formatDisabledTime->add(new DateInterval("P$day$type"));

                if ($type !== 'W') {

                    if ($subDay > 0) {
                        $formatDisabledTime->add(new DateInterval("P{$subDay}D"));
                        $subDay = 0;
                    }

                    if ($dayNumber > (int)$formatDisabledTime->format('d')) {

                        $subDay = $dayNumber - $formatDisabledTime->format('d') - $dayNumber;

                        $subDay = str_replace('-', '', (string)$subDay);
                        $formatDisabledTime->sub(new DateInterval("P{$subDay}D"));
                    }

                } else {
                    $dayOfWeek = $formatDisabledTime->format('w');

                    $formatDisabledTime->modify("-$dayOfWeek days");

                    if (!empty($disabledTime->custom_repeat_value)) {
                        for ($i = 0; $i < 7; $i++) {
                            $weekDays[] = $formatDisabledTime->format('Y-m-d');
                            $formatDisabledTime->modify('+1 day');
                        }
                    } else {
                        $formatDisabledTime->modify('+7 days');
                    }

                }

            }

            if (count($weekDays) == 0) {

                $newDate = $formatDisabledTime->format('Y-m-d');

                $disabledTimes = $this->collectTimes($startTime, $endTime, $skipMinute);

                $data = $this->setAfterDates($data, $disabledTimes, $formatDisabledTime, $newDate);

                continue;

            }

            $data = $this->eachByWeekDays($data, $weekDays, $disabledTime, $startTime, $endTime, $skipMinute);

        }

        return $data;
    }

    /**
     * @param array $data
     * @param array $weekDays
     * @param MasterDisabledTime $disabledTime
     * @param DateTime $startTime
     * @param DateTime $endTime
     * @param int $skipMinute
     * @return array
     * @throws Exception
     */
    private function eachByWeekDays(
        array $data,
        array $weekDays,
        MasterDisabledTime $disabledTime,
        DateTime $startTime,
        DateTime $endTime,
        int $skipMinute
    ): array
    {

        foreach ($weekDays as $weekDay) {

            $weekDay = new DateTime($weekDay);

            if (
                !empty($disabledTime->custom_repeat_value)
                && !in_array(Str::lower($weekDay->format('l')), $disabledTime->custom_repeat_value)
            ) {
                continue;
            }

            if (
                empty($disabledTime->custom_repeat_value) &&
                $weekDay->format('l') !== (new DateTime($disabledTime->date))->format('l')
            ) {
                continue;
            }

            $newDate = $weekDay->format('Y-m-d');

            $disabledTimes = $this->collectTimes($startTime, $endTime, $skipMinute);

            $data = $this->setAfterDates($data, $disabledTimes, $weekDay, $newDate);

        }

        return $data;
    }

    /**
     * @param array $data
     * @param array $disabledTimes
     * @param DateTime $formatDisabledTime
     * @param string $newDate
     * @return array
     * @throws Exception+
     */
    private function setAfterDates(array $data, array $disabledTimes, DateTime $formatDisabledTime, string $newDate): array
    {
        if (!isset($data[$newDate]) || $data[$newDate]['closed']) {
            return $data;
        }

        $data[$newDate] = [
            'date'           => $newDate,
            'month'          => $formatDisabledTime->format('m'),
            'day'            => $formatDisabledTime->format('d'),
            'name'           => $formatDisabledTime->format('l'),
            'closed'         => false,
            'times'          => $data[$newDate]['times'],
            'disabled_times' => $disabledTimes,
        ];

        $times = $data[$newDate]['times'];
        $disabledTimes = $data[$newDate]['disabled_times'];

        $startTime = new DateTime(key($disabledTimes));
        $endTime   = new DateTime(end($disabledTimes));

        $data[$newDate]['times'] = $this->removeBookedTimes($times, $startTime, $endTime);

        return $data;
    }

    /**
     * @param array $times
     * @param DateTime $startTime
     * @param DateTime $endTime
     * @return array
     * @throws Exception
     */
    private function removeBookedTimes(array $times, DateTime $startTime, DateTime $endTime): array
    {

        foreach ($times as $key => $time) {

            $time = new DateTime($time);

            if ($time >= $startTime && $time <= $endTime) {
                unset($times[$key]);
            }

        }

        return array_values($times);
    }

    /**
     * @param DateTime $lastTime
     * @param DateTime $closedTime
     * @param int $minute
     * @param bool $skip
     * @return array
     */
    private function collectTimes(DateTime $lastTime, DateTime $closedTime, int $minute, bool $skip = true): array
    {
        $times = [];

        $isFirst = true;

        $lastTime = clone $lastTime;

        while ($lastTime < $closedTime) {

            if (!$isFirst) {
                $lastTime->modify(($skip ? '+' : '-') . "$minute minute");
            }

            $times[$lastTime->format('H:i')] = $lastTime->format('H:i');

            // Do not delete!
            if ($skip && $lastTime > $closedTime || !$isFirst && !$skip && $lastTime < $closedTime) {
                break;
            }

            $isFirst = false;
        }

        return $times;
    }

    /**
     * @param array $data
     * @param array|null $disabledTimes
     * @return array
     */
    public function mergeTimes(array $data, ?array $disabledTimes): array
    {
        if (empty($disabledTimes)) {
            return $data;
        }

        $disabledTimes = collect($disabledTimes);

        $chunkDisabledTimes = $disabledTimes->chunk(2);

        foreach ($data['times'] as $key => $time) {

            foreach ($chunkDisabledTimes as $value) {

                [$min, $max] = [$value->min(), $value->max()];

                if ($time >= $min && $time <= $max) {
                    unset($data['times'][$key]);
                }

            }

        }

        $data['times'] = array_values($data['times']);
        $data['disabled_times'] = $disabledTimes->unique()->sort()->toArray();

        return $data;
    }

}
