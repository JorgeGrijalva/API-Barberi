<?php
declare(strict_types=1);

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * App\Models\CartDetail
 *
 * @property int $id
 * @property int $shop_id
 * @property int $user_cart_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Shop|null $shop
 * @property-read UserCart $userCart
 * @property-read CartDetailProduct|HasOne|null $cartDetailProduct
 * @property-read CartDetailProduct[]|HasMany|Collection $cartDetailProducts
 * @property-read int|null $cart_detail_products_count
 * @property-read int|null $cart_detail_products_sum_total_price
 * @property-read int|null $cart_detail_products_sum_discount
 * @property-read int|null $shop_tax
 * @property-read int|null $discount
 * @property-read int|null $total_price
 * @property-read int|null $coupon_price
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self wherePrice($value)
 * @method static Builder|self whereQuantity($value)
 * @method static Builder|self whereStockId($value)
 * @method static Builder|self whereUpdatedAt($value)
 * @method static Builder|self whereUserCartId($value)
 * @mixin Eloquent
 */
class CartDetail extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function userCart(): BelongsTo
    {
        return $this->belongsTo(UserCart::class);
    }

    public function cartDetailProduct(): HasOne
    {
        return $this->hasOne(CartDetailProduct::class);
    }

    public function cartDetailProducts(): HasMany
    {
        return $this->hasMany(CartDetailProduct::class);
    }
}
