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
 * App\Models\WarehouseClosedDate
 *
 * @property int $id
 * @property int $warehouse_id
 * @property Carbon|null $date
 * @property Shop|null $shop
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Warehouse|null $warehouse
 * @method static Builder|self filter(array $filter)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereUpdatedAt($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereWarehouseId($value)
 * @mixin Eloquent
 */
class WarehouseClosedDate extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function scopeFilter($query, array $filter) {
        $query
            ->when(data_get($filter, 'warehouse_id'),   fn($q, $warehouseId) => $q->where('warehouse_id', $warehouseId))
            ->when(data_get($filter, 'date_from'),      fn($q, $dateFrom)    => $q->where('date', '>=', $dateFrom))
            ->when(data_get($filter, 'date_to'),        fn($q, $dateTo)      => $q->where('date', '<=', $dateTo));
    }
}
