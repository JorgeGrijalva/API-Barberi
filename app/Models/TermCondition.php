<?php
declare(strict_types=1);

namespace App\Models;

use Database\Factories\TermConditionFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * App\Models\TermCondition
 *
 * @property int $id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read TermConditionTranslation|null $translation
 * @property-read Collection|TermConditionTranslation[] $translations
 * @property-read int|null $translations_count
 * @method static TermConditionFactory factory(...$parameters)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereUpdatedAt($value)
 * @mixin Eloquent
 */
class TermCondition extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    // Translations
    public function translations(): HasMany
    {
        return $this->hasMany(TermConditionTranslation::class);
    }

    public function translation(): HasOne
    {
        return $this->hasOne(TermConditionTranslation::class);
    }

}
