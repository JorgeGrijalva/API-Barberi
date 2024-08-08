<?php
declare(strict_types=1);

namespace App\Models;

use App\Traits\SetCurrency;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * App\Models\Bonus
 *
 * @property int $id
 * @property int|null $stock_id
 * @property int $bonus_quantity
 * @property int|null $bonus_stock_id
 * @property int|null $value
 * @property int|null $rate_value
 * @property string|null $type
 * @property Carbon|null $expired_at
 * @property int $status
 * @property int $shop_id
 * @property Shop $shop
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Stock|null $stock
 * @property-read Stock|null $bonusStock
 * @method static Builder|self active()
 * @method static Builder|self filter(array $filter)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereStockId($value)
 * @method static Builder|self whereBonusQuantity($value)
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereExpiredAt($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereOrderAmount($value)
 * @method static Builder|self whereShopId($value)
 * @method static Builder|self whereStatus($value)
 * @method static Builder|self whereUpdatedAt($value)
 * @mixin Eloquent
 */
class Bonus extends Model
{
    use HasFactory, SetCurrency;

    const TYPE_COUNT = 'count';
    const TYPE_SUM   = 'sum';

    const TYPES = [
        self::TYPE_COUNT    => self::TYPE_COUNT,
        self::TYPE_SUM      => self::TYPE_SUM,
    ];

    protected $guarded = ['id'];

    protected $casts = [
        'type'              => 'string',
        'bonus_quantity'    => 'integer',
        'bonus_stock_id'    => 'integer',
        'value'             => 'integer',
        'status'            => 'bool',
        'expired_at'        => 'datetime',
        'created_at'        => 'datetime',
        'updated_at'        => 'datetime',
    ];

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }

    public function bonusStock(): BelongsTo
    {
        return $this->belongsTo(Stock::class, 'bonus_stock_id');
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function getRateValueAttribute(): float|int|null
    {
        if (request()->is('api/v1/dashboard/user/*') || request()->is('api/v1/rest/*')) {
            return $this->type === self::TYPE_SUM ? $this->value * $this->currency() : $this->value;
        }

        return $this->value;
    }

    public function scopeActive($query): Builder
    {
        /** @var Bonus $query */
        return $query->where('status', true)->whereDate('expired_at', '>', now());
    }

    public function scopeFilter($query, array $filter) {
        $query
            ->when(data_get($filter, 'type'), fn($q, $type) => $q->where('type', $type))
            ->when(data_get($filter, 'status'), fn($q, $status) => $q->where('status', $status))
            ->when(data_get($filter, 'shop_id'), fn($q, $shopId) => $q->where('shop_id', $shopId))
            ->when(data_get($filter, 'stock_id'), fn($q, $stockId) => $q->where('stock_id', $stockId))
            ->when(data_get($filter, 'expired_at_from'),
                fn($q, $from) => $q->where('expired_at', '>=', date('Y-m-d 00:00:01', strtotime($from)))
            )
            ->when(data_get($filter, 'expired_at_to'),
                fn($q, $to) => $q->where('expired_at', '>=', date('Y-m-d 00:00:01', strtotime($to)))
            )
            ->when(data_get($filter, 'bonus_stock_id'), fn($q, $id) => $q->where('bonus_stock_id', $id));
    }
}
