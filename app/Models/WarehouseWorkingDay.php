<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Eloquent;

/**
 * App\Models\WarehouseWorkingDay
 *
 * @property int $id
 * @property int $warehouse_id
 * @property string $day
 * @property string|null $from
 * @property string|null $to
 * @property boolean|null $disabled
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Warehouse|null $warehouse
 * @method static Builder|self filter($filter = [])
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereUpdatedAt($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereWarehouseId($value)
 * @method static Builder|self whereDay($value)
 * @method static Builder|self whereFrom($value)
 * @method static Builder|self whereTo($value)
 * @mixin Eloquent
 */
class WarehouseWorkingDay extends Model
{
    protected $guarded = ['id'];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function scopeFilter($query, array $filter)
    {
        return $query
            ->when(data_get($filter, 'warehouse_id'),      fn($q, $warehouseId) => $q->where('warehouse_id', $warehouseId))
            ->when(data_get($filter, 'day'),               fn($q, $day)         => $q->where('day', $day))
            ->when(data_get($filter, 'from'),              fn($q, $from)        => $q->where('from', '>=', $from))
            ->when(data_get($filter, 'to'),                fn($q, $to)          => $q->where('to', '<=', $to))
            ->when(data_get($filter, 'disabled'),          fn($q, $disabled)    => $q->where('disabled', $disabled));
    }
}
