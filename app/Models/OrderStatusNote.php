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
 * App\Models\OrderStatusNote
 *
 * @property int $id
 * @property int $order_id
 * @property string $status
 * @property array $notes
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self filter(array $filter)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereOrderId($value)
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereUpdatedAt($value)
 * @mixin Eloquent
 */
class OrderStatusNote extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'notes' => 'array',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function scopeFilter(Builder $query, array $filter) {
        return $query
            ->when(@$filter['order_id'], fn($q, $orderId) => $q->where('order_id', $orderId))
            ->when(@$filter['status'],   fn($q, $status)  => $q->where('status',   $status));
    }
}
