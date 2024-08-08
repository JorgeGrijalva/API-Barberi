<?php
declare(strict_types=1);

namespace App\Models;

use App\Traits\Loadable;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * App\Models\CartDetail
 *
 * @property int $id
 * @property int $stock_id
 * @property int $cart_detail_id
 * @property int $parent_id
 * @property int $quantity
 * @property float|null $price
 * @property float|null $discount
 * @property boolean $bonus
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property double $rate_price
 * @property double $rate_discount
 * @property-read Stock|null $stock
 * @property-read CartDetail|null $cartDetail
 * @property-read self|null $parent
 * @property-read Collection|self[] $children
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereBonus($value)
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self wherePrice($value)
 * @method static Builder|self whereQuantity($value)
 * @method static Builder|self whereStockId($value)
 * @method static Builder|self whereUpdatedAt($value)
 * @method static Builder|self whereUserCartId($value)
 * @mixin Eloquent
 */
class CartDetailProduct extends Model
{
    use HasFactory, Loadable;

    protected $guarded = ['id'];

    protected $casts = [
        'bonus' => 'bool'
    ];

    public function getRatePriceAttribute(): float
    {
        return request()->is('api/v1/dashboard/user/*') || request()->is('api/v1/rest/*') ?
            $this->price * $this->cartDetail?->userCart?->cart?->rate :
            $this->price;
    }

    public function getRateDiscountAttribute(): float
    {
        return request()->is('api/v1/dashboard/user/*') || request()->is('api/v1/rest/*') ?
            $this->discount * $this->cartDetail?->userCart?->cart?->rate :
            $this->discount;
    }

    public function cartDetail(): BelongsTo
    {
        return $this->belongsTo(CartDetail::class);
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
