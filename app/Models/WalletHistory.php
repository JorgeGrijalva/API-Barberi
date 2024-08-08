<?php
declare(strict_types=1);

namespace App\Models;

use App\Traits\Payable;
use App\Traits\SetCurrency;
use Database\Factories\WalletHistoryFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Support\Carbon;

/**
 * App\Models\WalletHistory
 *
 * @property int $id
 * @property string $uuid
 * @property string $wallet_uuid
 * @property int|null $transaction_id
 * @property string $type
 * @property float $price
 * @property float $price_rate
 * @property string|null $note
 * @property string $status
 * @property int $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $author
 * @property-read User|null $user
 * @property-read Wallet|null $wallet
 * @property-read Transaction|null $transaction
 * @method static WalletHistoryFactory factory(...$parameters)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereCreatedBy($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereNote($value)
 * @method static Builder|self wherePrice($value)
 * @method static Builder|self whereStatus($value)
 * @method static Builder|self whereTransactionId($value)
 * @method static Builder|self whereType($value)
 * @method static Builder|self whereUpdatedAt($value)
 * @method static Builder|self whereUuid($value)
 * @method static Builder|self whereWalletUuid($value)
 * @mixin Eloquent
 */
class WalletHistory extends Model
{
    use HasFactory, SetCurrency, Payable;

    protected $guarded = ['id'];

    const PROCESSED = 'processed';
    const PAID      = 'paid';
    const REJECTED  = 'rejected';
    const CANCELED  = 'canceled';

    const TYPES     = [
        'topup',
        'withdraw',
        'referral_from_topup',
        'referral_from_withdraw',
    ];

    const STATUTES = [
        self::PROCESSED => self::PROCESSED,
        self::PAID      => self::PAID,
        self::REJECTED  => self::REJECTED,
        self::CANCELED  => self::CANCELED,
    ];

    public function getPriceRateAttribute(): float
    {
        if (request()->is('api/v1/dashboard/user/*') || request()->is('api/v1/rest/*')) {
            return $this->price * $this->currency();
        }

        return $this->price;
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'wallet_uuid', 'uuid');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function user(): HasOneThrough
    {
        return $this->hasOneThrough(User::class, Wallet::class,
            'uuid', 'id', 'wallet_uuid', 'user_id');
    }
}
