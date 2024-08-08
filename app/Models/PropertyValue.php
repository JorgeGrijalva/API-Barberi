<?php
declare(strict_types=1);

namespace App\Models;

use App\Traits\Loadable;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * App\Models\PropertyValue
 *
 * @property int $id
 * @property int $property_group_id
 * @property string $img
 * @property string $value
 * @property boolean $active
 * @property-read Collection|Gallery[] $galleries
 * @property-read int|null $galleries_count
 * @property-read PropertyGroup $group
 * @property-read Collection|Product[] $products
 * @property-read int|null $products_count
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereActive($value)
 * @method static Builder|self wherePropertyGroupId($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereValue($value)
 * @mixin Eloquent
 */
class PropertyValue extends Model
{
    use Loadable;

    protected $guarded = ['id'];

    protected $casts = [
        'active' => 'bool',
    ];

    public $timestamps = false;

    public function group(): BelongsTo
    {
        return $this->belongsTo(PropertyGroup::class, 'property_group_id');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, ProductProperty::class);
    }
}
