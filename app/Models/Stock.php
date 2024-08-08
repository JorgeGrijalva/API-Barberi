<?php
declare(strict_types=1);

namespace App\Models;

use App\Traits\Loadable;
use App\Traits\SetCurrency;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * App\Models\Stock
 *
 * @property int $id
 * @property float $price
 * @property int $quantity
 * @property string $sku
 * @property int $product_id
 * @property string|null $bonus_expired_at
 * @property string|null $discount_expired_at
 * @property int|null $discount_id
 * @property int|null $o_count
 * @property int|null $od_count
 * @property string $img
 * @property Carbon|null $deleted_at
 * @property-read double|null $actual_discount
 * @property-read double|null $rate_actual_discount
 * @property-read double|null $tax_price
 * @property-read double|null $rate_tax_price
 * @property-read double|null $total_price
 * @property-read double|null $rate_total_price
 * @property-read double|null $rate_price
 * @property-read Bonus|null $bonus
 * @property-read Discount|null $discount
 * @property-read Product $product
 * @property-read Collection|OrderDetail[] $orderDetails
 * @property-read OrderDetail|null $orderDetail
 * @property-read int|null $order_details_count
 * @property-read Collection|CartDetail[] $cartDetails
 * @property-read int $cart_details_count
 * @property-read Collection|Bonus[] $bonusByShop
 * @property-read int $bonus_by_shop_count
 * @property-read StockExtra|null $stockExtra
 * @property-read Collection|StockExtra[] $stockExtras
 * @property-read int|null $stock_extras_count
 * @property-read Collection|ModelLog[] $logs
 * @property-read int|null $logs_count
 * @property-read Collection|WholeSalePrice $wholeSalePrice
 * @property-read Collection|WholeSalePrice[] $wholeSalePrices
 * @property-read int|null $whole_sale_prices_count
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self increment($column, $amount = 1, array $extra = [])
 * @method static Builder|self decrement($column, $amount = 1, array $extra = [])
 * @method static Builder|self whereId($value)
 * @method static Builder|self wherePrice($value)
 * @method static Builder|self whereQuantity($value)
 * @method static Builder|self whereSku($value)
 * @mixin Eloquent
 */
class Stock extends Model
{
    use SetCurrency, Loadable, SoftDeletes;

    protected $guarded = ['id'];
    protected $casts   = [
        'discount_expired_at' => 'datetime:Y-m-d'
    ];

    public $timestamps = false;

    public function getActualDiscountAttribute()
    {
        if ($this->discount_expired_at < now()->format('Y-m-d')) {
            return 0;
        }

        /** @var Discount $discount */
        $discount = $this->discount()
            ->where('start', '<=', today())
            ->where('end', '>=', today())
            ->where('active', 1)
            ->first();

        if (!$discount?->type) {
            return 0;
        }

        $price = $discount->type == 'percent' ? ($discount->price / 100 * ($this->price + $this->tax_price)) : $discount->price;

        return max($price, 0);
    }

    public function getRateActualDiscountAttribute(): float|int|null
    {
        if (request()->is('api/v1/dashboard/user/*') || request()->is('api/v1/rest/*')) {
            return $this->actual_discount * $this->currency();
        }

        return $this->actual_discount;
    }

    public function getTotalPriceAttribute()
    {
        return max($this->price - $this->actual_discount + $this->tax_price, 0);
    }

    public function getRateTotalPriceAttribute(): float|int|null
    {
        if (request()->is('api/v1/dashboard/user/*') || request()->is('api/v1/rest/*')) {
            return $this->total_price * $this->currency();
        }

        return $this->total_price;
    }

    public function getRatePriceAttribute(): float|int|null
    {
        if (request()->is('api/v1/dashboard/user/*') || request()->is('api/v1/rest/*')) {
            return $this->price * $this->currency();
        }

        return $this->price;
    }

    public function getTaxPriceAttribute()
    {
        return max(($this->price / 100) * ($this->product?->tax ?? 1), 0);
    }

    public function getRateTaxPriceAttribute(): float|int|null
    {
        if (request()->is('api/v1/dashboard/user/*') || request()->is('api/v1/rest/*')) {
            return $this->tax_price * $this->currency();
        }

        return $this->tax_price;
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class);
    }

    public function bonus(): HasOne
    {
        return $this->hasOne(Bonus::class);
    }

    public function bonusByShop(): HasMany
    {
        return $this->hasMany(Bonus::class, 'bonus_stock_id');
    }

    public function orderDetails(): HasMany
    {
        return $this->hasMany(OrderDetail::class);
    }

    public function orderDetail(): HasOne
    {
        return $this->hasOne(OrderDetail::class);
    }

    public function cartDetails(): HasMany
    {
        return $this->hasMany(CartDetail::class);
    }

    public function stockExtra(): HasOne
    {
        return $this->hasOne(StockExtra::class);
    }

    public function stockExtras(): HasMany
    {
        return $this->hasMany(StockExtra::class);
    }

    public function logs(): MorphMany
    {
        return $this->morphMany(ModelLog::class, 'model');
    }

    public function wholeSalePrice(): HasOne
    {
        return $this->hasOne(WholeSalePrice::class);
    }

    public function wholeSalePrices(): HasMany
    {
        return $this->hasMany(WholeSalePrice::class);
    }

}
