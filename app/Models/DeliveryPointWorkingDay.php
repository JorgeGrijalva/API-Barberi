<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Eloquent;

/**
 * App\Models\DeliveryPointWorkingDay
 *
 * @property int $id
 * @property int $delivery_point_id
 * @property string $day
 * @property string|null $from
 * @property string|null $to
 * @property boolean|null $disabled
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property DeliveryPoint|null $deliveryPoint
 * @method static Builder|self filter($filter = [])
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereUpdatedAt($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereDeliveryPointId($value)
 * @method static Builder|self whereDay($value)
 * @method static Builder|self whereFrom($value)
 * @method static Builder|self whereTo($value)
 * @mixin Eloquent
 */
class DeliveryPointWorkingDay extends Model
{
    protected $guarded = ['id'];

    public function deliveryPoint(): BelongsTo
    {
        return $this->belongsTo(DeliveryPoint::class);
    }

    public function scopeFilter($query, array $filter)
    {
        return $query
            ->when(data_get($filter, 'delivery_point_id'), fn($q, $deliveryPointId) => $q->where('delivery_point_id', $deliveryPointId))
            ->when(data_get($filter, 'day'),               fn($q, $day)      => $q->where('day', $day))
            ->when(data_get($filter, 'from'),              fn($q, $from)     => $q->where('from', '>=', $from))
            ->when(data_get($filter, 'to'),                fn($q, $to)       => $q->where('to', '<=', $to))
            ->when(data_get($filter, 'disabled'),          fn($q, $disabled) => $q->where('disabled', $disabled));
    }
}
