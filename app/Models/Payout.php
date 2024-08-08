<?php
declare(strict_types=1);

namespace App\Models;

use App\Traits\Loadable;
use Database\Factories\PayoutFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * App\Models\Payout
 *
 * @property int $id
 * @property string $status
 * @property int $created_by
 * @property int $approved_by
 * @property int $currency_id
 * @property int $payment_id
 * @property string $cause
 * @property string $answer
 * @property double $price
 * @property User|null $createdBy
 * @property User|null $approvedBy
 * @property Currency|null $currency
 * @property Payment|null $payment
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static PayoutFactory factory(...$parameters)
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
class Payout extends Model
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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function scopeFilter($query, $filter = [])
    {
        return $query
            ->when(data_get($filter, 'created_by'), function ($q, $createdBy) {
                $q->where('created_by', $createdBy);
            })
            ->when(data_get($filter, 'approved_by'), function ($q, $approvedBy) {
                $q->where('approved_by', $approvedBy);
            })
            ->when(data_get($filter, 'currency_id'), function ($q, $currencyId) {
                $q->where('currency_id', $currencyId);
            })
            ->when(data_get($filter, 'payment_id'), function ($q, $paymentId) {
                $q->where('payment_id', $paymentId);
            })
            ->when(data_get($filter, 'status'), function ($q, $status) {
                $q->where('status', $status);
            })
            ->when(data_get($filter, 'cause'), function ($q, $cause) {
                $q->where('cause', 'like', "%$cause%");
            })
            ->when(data_get($filter, 'answer'), function ($q, $answer) {
                $q->where('answer', 'like', "%$answer%");
            });
    }
}
