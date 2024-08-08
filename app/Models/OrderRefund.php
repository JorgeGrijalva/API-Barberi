<?php
declare(strict_types=1);

namespace App\Models;

use  App\Traits\Loadable;
use Database\Factories\TransactionFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Schema;

/**
 * App\Models\OrderRefund
 *
 * @property int $id
 * @property string $status
 * @property string $cause
 * @property string $answer
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Order|null $order
 * @property Collection|Gallery[] $galleries
 * @method static TransactionFactory factory(...$parameters)
 * @method static Builder|self filter($filter = [])
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereUpdatedAt($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereCause($value)
 * @method static Builder|self whereAnswer($value)
 * @method static Builder|self whereStatus($value)
 * @mixin Eloquent
 */
class OrderRefund extends Model
{
    use HasFactory, Loadable;

    protected $guarded = ['id'];

    const STATUS_PENDING    = 'pending';
    const STATUS_ACCEPTED   = 'accepted';
    const STATUS_CANCELED   = 'canceled';

    const STATUSES = [
        self::STATUS_PENDING    => self::STATUS_PENDING,
        self::STATUS_ACCEPTED   => self::STATUS_ACCEPTED,
        self::STATUS_CANCELED   => self::STATUS_CANCELED,
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function scopeFilter($query, $filter = [])
    {
        $column = $filter['column'] ?? 'id';

        if ($column !== 'id') {
            $column = Schema::hasColumn('order_refunds', $column) ? $column : 'id';
        }

        return $query
            ->when(data_get($filter, 'order_id'), function ($q, $orderId) {
                $q->where('order_id', $orderId);
            })
            ->when(data_get($filter, 'shop_id') || data_get($filter, 'user_id'), function ($q) use ($filter) {
                $q->whereHas('order', function ($builder) use ($filter) {

                    if (data_get($filter, 'shop_id')) {
                        $builder->whereHas('orderDetails', fn($q) => $q->where('shop_id', data_get($filter, 'shop_id')));
                    }

                    if (data_get($filter, 'user_id')) {
                        $builder->where('user_id', data_get($filter, 'user_id'));
                    }

                });
            })
            ->when(data_get($filter, 'status'), function ($q, $status) {
                $q->where('status', $status);
            })
            ->when(data_get($filter, 'cause'), function ($q, $cause) {
                $q->where('cause', 'like', "%$cause%");
            })
            ->when(data_get($filter, 'answer'), function ($q, $answer) {
                $q->where('answer', 'like', "%$answer%");
            })
            ->when(data_get($filter, 'search'), function (Builder $query, $search) {
                $query->where(function (Builder $q) use ($search) {
                    $q->where('answer', 'like', "%$search%")
                        ->orWhere('cause', 'like', "%$search%")
                        ->orWhere('id', $search)
                        ->orWhere('order_id', $search)
                        ->orWhereHas('order.user',
                            fn($q) => $q->where('firstname', 'like', "%$search%")->orWhere('lastname', 'like', "%$search%")
                        );
                });
            })
            ->orderBy($column, $filter['sort'] ?? 'desc');
    }
}
