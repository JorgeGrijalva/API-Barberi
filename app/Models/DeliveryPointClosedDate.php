<?php
declare(strict_types=1);

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * App\Models\DeliveryPointClosedDate
 *
 * @property int $id
 * @property int $delivery_point_id
 * @property Carbon|null $date
 * @property Shop|null $shop
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property DeliveryPoint|null $deliveryPoint
 * @method static Builder|self filter(array $filter)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereUpdatedAt($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereDeliveryPointId($value)
 * @mixin Eloquent
 */
class DeliveryPointClosedDate extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function deliveryPoint(): BelongsTo
    {
        return $this->belongsTo(DeliveryPoint::class);
    }

    public function scopeFilter($query, array $filter) {
        $query
            ->when(data_get($filter, 'delivery_point_id'), fn($q, $deliveryPointId) => $q->where('delivery_point_id', $deliveryPointId))
            ->when(data_get($filter, 'date_from'),  fn($q, $dateFrom)   => $q->where('date', '>=', $dateFrom))
            ->when(data_get($filter, 'date_to'),    fn($q, $dateTo)     => $q->where('date', '<=', $dateTo));
    }
}
