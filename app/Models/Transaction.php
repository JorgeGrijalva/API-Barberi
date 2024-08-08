<?php
declare(strict_types=1);

namespace App\Models;

use Database\Factories\TransactionFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * App\Models\Transaction
 *
 * @property int $id
 * @property string $payable_type
 * @property int $payable_id
 * @property float $price
 * @property int|null $user_id
 * @property int|null $payment_sys_id
 * @property string|null $payment_trx_id
 * @property string|null $note
 * @property string|null $perform_time
 * @property string|null $refund_time
 * @property string $status
 * @property string $status_description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Order|Wallet|Booking|UserGiftCart|UserMemberShip|null $payable
 * @property-read Payment|null $paymentSystem
 * @property-read User|null $user
 * @method static TransactionFactory factory(...$parameters)
 * @method static Builder|self filter($filter = [])
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereNote($value)
 * @method static Builder|self wherePayableId($value)
 * @method static Builder|self wherePayableType($value)
 * @method static Builder|self wherePaymentSysId($value)
 * @method static Builder|self wherePaymentTrxId($value)
 * @method static Builder|self wherePerformTime($value)
 * @method static Builder|self wherePrice($value)
 * @method static Builder|self whereRefundTime($value)
 * @method static Builder|self whereStatus($value)
 * @method static Builder|self whereStatusDescription($value)
 * @method static Builder|self whereUpdatedAt($value)
 * @method static Builder|self whereUserId($value)
 * @mixin Eloquent
 */
class Transaction extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    const STATUS_PROGRESS      = 'progress';
    const STATUS_PAID          = 'paid';
    const STATUS_CANCELED      = 'canceled';
    const STATUS_REJECTED      = 'rejected';
    const STATUS_REFUND        = 'refund';
    const STATUS_REFUND_FAILED = 'refund_failed';

    const STATUSES = [
        self::STATUS_PROGRESS      => self::STATUS_PROGRESS,
        self::STATUS_PAID          => self::STATUS_PAID,
        self::STATUS_CANCELED      => self::STATUS_CANCELED,
        self::STATUS_REJECTED      => self::STATUS_REJECTED,
        self::STATUS_REFUND        => self::STATUS_REFUND,
        self::STATUS_REFUND_FAILED => self::STATUS_REFUND_FAILED,
    ];

    public function payable(): MorphTo
    {
        return $this->morphTo('payable');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function paymentSystem(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_sys_id');
    }

    public function scopeFilter($query, $filter = [])
    {
        return $query
            ->when(data_get($filter, 'model') == 'orders' , function (Builder $query) {
                $query->where(['payable_type' => Order::class]);
            })
            ->when(data_get($filter, 'shop_id'), function (Builder $q, $shopId) use ($filter) {

                if (isset($filter['model']) && $filter['model'] === 'bookings') {
                    return $q->whereHasMorph('payable', Booking::class, function (Builder $b) use ($shopId) {
                        $b->where('shop_id', $shopId);
                    });
                }

                return $q->whereHasMorph('payable', Order::class, function (Builder $b) use ($shopId) {
                    $b->where('shop_id', $shopId);
                });
            })
            ->when(data_get($filter, 'model') == 'wallet', fn($q) => $q->where(['payable_type' => Wallet::class]))
            ->when(data_get($filter, 'user_id'), fn($q, $userId) => $q->where('user_id', $userId))
            ->when(data_get($filter, 'status'), fn($q, $status) => $q->where('status', $status));
    }
}
