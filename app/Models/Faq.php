<?php
declare(strict_types=1);

namespace App\Models;

use Database\Factories\FaqFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * App\Models\Faq
 *
 * @property int $id
 * @property string $uuid
 * @property string|null $type
 * @property boolean $active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read FaqTranslation|null $translation
 * @property-read Collection|FaqTranslation[] $translations
 * @property-read int|null $translations_count
 * @method static FaqFactory factory(...$parameters)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereActive($value)
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereType($value)
 * @method static Builder|self whereUpdatedAt($value)
 * @method static Builder|self whereUuid($value)
 * @mixin Eloquent
 */
class Faq extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'active' => 'bool',
    ];

    public function translations(): HasMany
    {
        return $this->hasMany(FaqTranslation::class);
    }

    public function translation(): HasOne
    {
        return $this->hasOne(FaqTranslation::class);
    }
}
