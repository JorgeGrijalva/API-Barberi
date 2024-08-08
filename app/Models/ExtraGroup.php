<?php
declare(strict_types=1);

namespace App\Models;

use Database\Factories\ExtraGroupFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * App\Models\ExtraGroup
 *
 * @property int $id
 * @property string|null $type
 * @property int $active
 * @property int $shop_id
 * @property Shop|null $shop
 * @property-read Collection|ExtraValue[] $extraValues
 * @property-read int|null $extra_values_count
 * @property-read ExtraGroupTranslation|null $translation
 * @property-read Collection|ExtraGroupTranslation[] $translations
 * @property-read int|null $translations_count
 * @method static ExtraGroupFactory factory(...$parameters)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereActive($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereType($value)
 * @mixin Eloquent
 */
class ExtraGroup extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
      'active' => 'bool',
    ];

    public $timestamps = false;


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
        return $this->hasMany(ExtraGroupTranslation::class);
    }

    public function translation(): HasOne
    {
        return $this->hasOne(ExtraGroupTranslation::class);
    }

    public function extraValues(): HasMany
    {
        return $this->hasMany(ExtraValue::class);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }
}
