<?php
declare(strict_types=1);

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\StockExtra
 *
 * @property int $id
 * @property int $stock_id
 * @property int $extra_value_id
 * @property int $extra_group_id
 * @property ExtraValue|null $extraValue
 * @property ExtraGroup|null $extraGroup
 * @property Stock|null $stock
 * @property ExtraValue|null $value
 * @property ExtraGroup|null $group
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereExtraValueId($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereStockId($value)
 * @mixin Eloquent
 */
class StockExtra extends Model
{
    protected $guarded = ['id'];

    public $timestamps = false;

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }

    public function value(): BelongsTo
    {
        return $this->belongsTo(ExtraValue::class, 'extra_value_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(ExtraGroup::class, 'extra_group_id');
    }
}
