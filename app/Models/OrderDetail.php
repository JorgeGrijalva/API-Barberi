<?php
declare(strict_types=1);

namespace App\Models;

use App\Traits\Loadable;
use Database\Factories\OrderDetailFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * App\Models\OrderDetail
 *
 * @property int $id
 * @property int $order_id
 * @property int $stock_id
 * @property int $replace_stock_id
 * @property int $replace_quantity
 * @property int $replace_note
 * @property int $origin_price
 * @property int $total_price
 * @property int $tax
 * @property int $discount
 * @property int $quantity
 * @property int $bonus
 * @property string $note
 * @property string $rate_tax
 * @property string $rate_discount
 * @property string $rate_origin_price
 * @property string $rate_total_price
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Order|null $order
 * @property-read Stock|null $stock
 * @property-read Stock|null $replaceStock
 * @method static OrderDetailFactory factory(...$parameters)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereOrderId($value)
 * @method static Builder|self whereUpdatedAt($value)
 * @mixin Eloquent
 */
class OrderDetail extends Model
{
    use HasFactory, Loadable;

    protected $guarded = ['id'];

    public function getRateTotalPriceAttribute(): ?float
    {
        if (request()->is('api/v1/dashboard/user/*') || request()->is('api/v1/rest/*')) {
            $rate = (double)$this->order?->rate;
            return $this->total_price * ($rate <= 0 ? 1 : $rate);
        }

        return $this->total_price;
    }

    public function getRateOriginPriceAttribute(): ?float
    {
        if (request()->is('api/v1/dashboard/user/*') || request()->is('api/v1/rest/*')) {
            $rate = (double)$this->order?->rate;
            return $this->origin_price * ($rate <= 0 ? 1 : $rate);
        }

        return $this->origin_price;
    }

    public function getRateDiscountAttribute(): ?float
    {
        if (request()->is('api/v1/dashboard/user/*') || request()->is('api/v1/rest/*')) {
            $rate = (double)$this->order?->rate;
            return $this->discount * ($rate <= 0 ? 1 : $rate);
        }

        return $this->discount;
    }

    public function getRateTaxAttribute(): ?float
    {
        if (request()->is('api/v1/dashboard/user/*') || request()->is('api/v1/rest/*')) {
            $rate = (double)$this->order?->rate;
            return $this->tax * ($rate <= 0 ? 1 : $rate);
        }

        return $this->tax;
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class)->withTrashed();
    }

    public function replaceStock(): BelongsTo
    {
        return $this->belongsTo(Stock::class)->withTrashed();
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

}
