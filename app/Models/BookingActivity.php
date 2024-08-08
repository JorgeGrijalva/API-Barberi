<?php
declare(strict_types=1);

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * App\Models\BookingActivity
 *
 * @property int $id
 * @property int $booking_id
 * @property int $user_id
 * @property string $note
 * @property string $type
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @method static Builder|self active()
 * @method static Builder|self filter(array $filter)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereId($value)
 * @mixin Eloquent
 */
class BookingActivity extends Model
{
    public $guarded = ['id'];

    protected $casts = [
        'id'         => 'int',
        'booking_id' => 'int',
        'user_id'    => 'int',
        'note'       => 'string',
        'type'       => 'string',
    ];

    const TYPES = [
        'reschedule',
        'update_master',
        'update_price',
        'update_service',
        'update_gift_cart',
        'update_user_member_ship',
    ] + Booking::STATUSES;

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeFilter($query, array $filter) {
        $query
            ->when(data_get($filter, 'user_id'), fn($q, $id)   => $q->where('user_id', $id))
            ->when(data_get($filter, 'type'),    fn($q, $type) => $q->where('type', $type));
    }
}
