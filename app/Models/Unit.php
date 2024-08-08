<?php
declare(strict_types=1);

namespace App\Models;

use Database\Factories\UnitFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * App\Models\Unit
 *
 * @property int $id
 * @property boolean $active
 * @property string $position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read UnitTranslation|null $translation
 * @property-read Collection|UnitTranslation[] $translations
 * @property-read int|null $translations_count
 * @method static UnitFactory factory(...$parameters)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereActive($value)
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self wherePosition($value)
 * @method static Builder|self whereUpdatedAt($value)
 * @mixin Eloquent
 */
class Unit extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'active' => 'bool',
    ];

    // Translations
    public function translations(): HasMany
    {
        return $this->hasMany(UnitTranslation::class);
    }

    public function translation(): HasOne
    {
        return $this->hasOne(UnitTranslation::class);
    }

}
