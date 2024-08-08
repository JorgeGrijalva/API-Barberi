<?php
declare(strict_types=1);

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Schema;

/**
 * App\Models\GiftCart
 *
 * @property int $id
 * @property int $shop_id
 * @property string $time
 * @property boolean $active
 * @property double $price
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection|User[] $users
 * @property-read int|null $users_count
 * @property-read Shop|null $shop
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self filter(array $filter)
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereShopId($value)
 * @method static Builder|self whereUpdatedAt($value)
 * @mixin Eloquent
 */
class GiftCart extends Model
{
    protected $guarded = ['id'];

    // Translations
    public function translations(): HasMany
    {
        return $this->hasMany(GiftCartTranslation::class);
    }

    public function translation(): HasOne
    {
        return $this->hasOne(GiftCartTranslation::class);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(UserGiftCart::class);
    }

    public function scopeFilter($query, array $filter)
    {
        $column = $filter['column'] ?? 'id';

        if ($column !== 'id') {
            $column = Schema::hasColumn('gift_carts', $column) ? $column : 'id';
        }

        $query
            ->when(data_get($filter,'user_id'), function ($q, $userId) {
                $q->whereHas('users', fn($q) => $q->where('user_id', $userId));
            })
            ->when(data_get($filter, 'price'), fn ($q, $price) => $q->where('price', $price))
            ->when(request()->is('api/v1/rest/*'), fn($q)  => $q->where('active', true))
            ->when(data_get($filter, 'shop_id'), fn($q, $shopId) => $q->where('shop_id', $shopId))
            ->orderBy($column, $filter['sort'] ?? 'desc');
    }
}
