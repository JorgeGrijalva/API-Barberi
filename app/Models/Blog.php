<?php
declare(strict_types=1);

namespace App\Models;

use App\Traits\Loadable;
use App\Traits\Reviewable;
use Database\Factories\BlogFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * App\Models\Blog
 *
 * @property int $id
 * @property string $uuid
 * @property int $user_id
 * @property string $type
 * @property string|null $published_at
 * @property boolean $active
 * @property string|null $img
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int|null $r_count
 * @property int|null $r_avg
 * @property int|null $r_sum
 * @property-read Collection|Gallery[] $galleries
 * @property-read int|null $galleries_count
 * @property-read Collection|Review[] $reviews
 * @property-read int|null $reviews_count
 * @property-read BlogTranslation|null $translation
 * @property-read Collection|BlogTranslation[] $translations
 * @property-read int|null $translations_count
 * @method static BlogFactory factory(...$parameters)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereActive($value)
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereImg($value)
 * @method static Builder|self wherePublishedAt($value)
 * @method static Builder|self whereType($value)
 * @method static Builder|self whereUpdatedAt($value)
 * @method static Builder|self whereUserId($value)
 * @method static Builder|self whereUuid($value)
 * @mixin Eloquent
 */
class Blog extends Model
{
    use HasFactory, Loadable, Reviewable;

    protected $guarded = ['id'];

    protected $casts = [
        'active' => 'bool',
    ];

    const TYPES = [
        'blog'          => 1,
        'notification'  => 2,
    ];

    public function getTypeAttribute($value): string
    {
        return $value === 2 ? 'notification' : 'blog';
    }

    public function translations(): HasMany
    {
        return $this->hasMany(BlogTranslation::class);
    }

    public function translation(): HasOne
    {
        return $this->hasOne(BlogTranslation::class);
    }
}
