<?php
declare(strict_types=1);

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\ProductProperty
 *
 * @property int $id
 * @property int $product_id
 * @property int $property_group_id
 * @property int $property_value_id
 * @property-read Product|null $product
 * @property-read ExtraGroup|null $group
 * @property-read ExtraValue|null $value
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereExtraGroupId($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereProductId($value)
 * @mixin Eloquent
 */
class ProductProperty extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(PropertyGroup::class, 'property_group_id');
    }

    public function value(): BelongsTo
    {
        return $this->belongsTo(PropertyValue::class, 'property_value_id');
    }
}
