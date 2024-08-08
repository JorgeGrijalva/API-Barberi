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

/**
 * App\Models\PropertyGroup
 *
 * @property int $id
 * @property string|null $type
 * @property boolean $active
 * @property int|null $shop_id
 * @property Shop|null $shop
 * @property-read Collection|PropertyValue[] $propertyValues
 * @property-read int|null $property_values_count
 * @property-read PropertyGroupTranslation|null $translation
 * @property-read Collection|PropertyGroupTranslation[] $translations
 * @property-read int|null $translations_count
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereActive($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereType($value)
 * @mixin Eloquent
 */
class PropertyGroup extends Model
{
    protected $guarded = ['id'];

    public $timestamps = false;

    protected $casts = [
        'active' => 'bool',
    ];

    const TYPES = [
        'color',
        'text',
        'image'
    ];

    public function getTypes(): array
    {
        return self::TYPES;
    }

    public function translations(): HasMany
    {
        return $this->hasMany(PropertyGroupTranslation::class);
    }

    public function translation(): HasOne
    {
        return $this->hasOne(PropertyGroupTranslation::class);
    }

    public function propertyValues(): HasMany
    {
        return $this->hasMany(PropertyValue::class);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }
}
