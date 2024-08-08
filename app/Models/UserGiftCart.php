<?php
declare(strict_types=1);

namespace App\Models;

use App\Traits\Payable;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Schema;

/**
 * App\Models\UserGiftCart
 *
 * @property int $id
 * @property int $gift_cart_id
 * @property int $user_id
 * @property double $price
 * @property Carbon $expired_at
 * @property GiftCart $giftCart
 * @property User $user
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self filter(array $filter)
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereId($value)
 * @mixin Eloquent
 */
class UserGiftCart extends Model
{
    use HasFactory, Payable;

    protected $guarded = ['id'];

    public function giftCart(): BelongsTo
    {
        return $this->belongsTo(GiftCart::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeFilter($query, array $filter)
    {
        $column = $filter['column'] ?? 'id';

        if ($column !== 'id') {
            $column = Schema::hasColumn('user_gift_carts', $column) ? $column : 'id';
        }

        $query
            ->when(data_get($filter, 'user_id'), fn($q, $id) => $q->where('user_id', $id))
            ->when(data_get($filter, 'shop_id'), fn($q, $id) => $q->whereHas('giftCart', function ($q) use ($id) {
                $q->where('shop_id', $id);
            }))
            ->when(data_get($filter, 'price'),   fn($q, $price) => $q->where('price', $price))
            ->when(isset($filter['valid']),   fn($q) => $q->where('expired_at','>=',now()->format('Y-m-d')))
            ->when(data_get($filter, 'expired_from'), function ($q, $expiredFrom) use ($filter) {
                $expiredFrom = date('Y-m-d', strtotime($expiredFrom));

                $expiredTo = data_get($filter, 'expired_to', date('Y-m-d'));

                $expiredTo = date('Y-m-d', strtotime($expiredTo));

                $q->where([
                    ['expired_at', '>=', $expiredFrom],
                    ['expired_at', '<=', $expiredTo],
                ]);
            })
            ->orderBy($column, $filter['sort'] ?? 'desc');
    }
}
