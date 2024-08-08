<?php
declare(strict_types=1);

namespace App\Models;

use App\Traits\Payable;
use Database\Factories\WalletFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * App\Models\Wallet
 *
 * @property int $id
 * @property string $uuid
 * @property int $user_id
 * @property int $currency_id
 * @property float $price
 * @property float $price_rate
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Currency $currency
 * @property-read mixed $symbol
 * @property-read Collection|WalletHistory[] $histories
 * @property-read int|null $histories_count
 * @property-read Collection|Transaction[] $transactions
 * @property-read int|null $transactions_count
 * @property-read User $user
 * @method static WalletFactory factory(...$parameters)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereCurrencyId($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self wherePrice($value)
 * @method static Builder|self whereUpdatedAt($value)
 * @method static Builder|self whereUserId($value)
 * @method static Builder|self whereUuid($value)
 * @mixin Eloquent
 */
class Wallet extends Model
{
    use HasFactory, Payable;

    protected $guarded = ['id'];

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(WalletHistory::class, 'wallet_uuid', 'uuid');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function getPriceRateAttribute(): float
    {
        $currency = request('currency_id')
            ? Currency::currenciesList()->where('id', request('currency_id'))->first()
            : Currency::currenciesList()->where('default', 1)->first();

        return $this->price * $currency?->rate;
    }

    public function getSymbolAttribute()
    {
        $currency = Currency::currenciesList()->where('id', request('currency_id'))->first();

        if (empty($currency)) {
            $currency = Currency::currenciesList()->where('default', 1)->first();
        }

        return $currency?->symbol;
    }
}
