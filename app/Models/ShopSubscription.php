<?php
declare(strict_types=1);

namespace App\Models;

use App\Traits\Payable;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Schema;

/**
 * App\Models\ShopSubscription
 *
 * @property int $id
 * @property int $shop_id
 * @property int $subscription_id
 * @property string|null $expired_at
 * @property float|null $price
 * @property string|null $type
 * @property int $active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Shop $shop
 * @property-read Subscription|null $subscription
 * @property-read Transaction|null $transaction
 * @property-read Collection|Transaction[] $transactions
 * @property-read int|null $transactions_count
 * @method static Builder|self filter(array $filter)
 * @method static Builder|self actualSubscription()
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereActive($value)
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereExpiredAt($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self wherePrice($value)
 * @method static Builder|self whereShopId($value)
 * @method static Builder|self whereSubscriptionId($value)
 * @method static Builder|self whereType($value)
 * @method static Builder|self whereUpdatedAt($value)
 * @mixin Eloquent
 */
class ShopSubscription extends Model
{
    use HasFactory, Payable;

    protected $guarded = ['id'];

    protected $casts = [
        'active' => 'bool',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function scopeActualSubscription($query)
    {
        return $query->where('active', 1)
            ->where('expired_at', '>=', now());
    }

    public function scopeFilter($query, array $filter)
    {
        $column = $filter['column'] ?? 'id';

        if ($column !== 'id') {
            $column = Schema::hasColumn('shop_subscriptions', $column) ? $column : 'id';
        }

        $query
            ->when(data_get($filter, 'shop_id'),    fn($q, $shopId)    => $q->where('shop_id',    $shopId))
            ->when(data_get($filter, 'service_id'), fn($q, $serviceId) => $q->where('service_id', $serviceId))
            ->when(data_get($filter, 'active'),     fn($q, $active)    => $q->where('active',     $active))
            ->orderBy($column, $filter['sort'] ?? 'desc');
    }
}
