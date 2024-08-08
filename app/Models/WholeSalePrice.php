<?php
declare(strict_types=1);

namespace App\Models;

use App\Traits\SetCurrency;
use Carbon\Carbon;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\WholeSalePrice
 *
 * @property int $id
 * @property int $stock_id
 * @property float $min_quantity
 * @property float $max_quantity
 * @property float $price
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read double|null $rate_price
 * @property-read Stock|null $stock
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self increment($column, $amount = 1, array $extra = [])
 * @method static Builder|self decrement($column, $amount = 1, array $extra = [])
 * @method static Builder|self whereId($value)
 * @method static Builder|self wherePrice($value)
 * @method static Builder|self whereMinQuantity($value)
 * @method static Builder|self whereMaxQuantity($value)
 * @mixin Eloquent
 */
class WholeSalePrice extends Model
{
    use SetCurrency;

    protected $guarded = ['id'];
    protected $casts   = [
        'price'         => 'double',
        'stock_id'      => 'int',
        'min_quantity'  => 'int',
        'max_quantity'  => 'int',
    ];

    public $timestamps = false;

    public function getRatePriceAttribute(): float|int|null
    {
        if (request()->is('api/v1/dashboard/user/*') || request()->is('api/v1/rest/*')) {
            return $this->price * $this->currency();
        }

        return $this->price;
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }

}
