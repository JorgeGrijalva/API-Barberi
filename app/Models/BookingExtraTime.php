<?php
declare(strict_types=1);

namespace App\Models;

use Eloquent;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\BookingExtraTime
 * @property int $id
 * @property int $booking_id
 * @property int $price
 * @property int $duration
 * @property int $duration_type
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Booking $booking
 * @method static Builder|self filter(array $filter)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereId($value)
 * @mixin Eloquent
 */
class BookingExtraTime extends Model
{
    protected $guarded = ['id'];

    public const MINUTE = 'minute';
    public const HOUR   = 'hour';

    public const DURATION_TYPES = [self::MINUTE, self::HOUR];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function scopeFilter($query, array $filter = []) {
        $query
            ->when(isset($filter['duration_from']) && $filter['duration_to'], function($q) use ($filter) {
                $q->where('duration', '>=', $filter['duration_from'])->where('duration', '>=', $filter['duration_to']);
            })
            ->when(data_get($filter, 'duration_type'), fn($q, $type) => $q->where('duration_type', $type))
            ->when(data_get($filter, 'booking_id'),    fn($q, $id)   => $q->where('booking_id', $id));
    }
}
