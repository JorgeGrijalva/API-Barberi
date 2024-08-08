<?php
declare(strict_types=1);

namespace App\Models;

use Database\Factories\ReviewFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * App\Models\MetaTag
 *
 * @property int $id
 * @property string $path
 * @property int $model_id
 * @property string $model_type
 * @property string $title
 * @property string $keywords
 * @property string $description
 * @property string $h1
 * @property string $seo_text
 * @property string $canonical
 * @property string $robots
 * @property string $change_freq
 * @property string $priority
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static ReviewFactory factory(...$parameters)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereModelId($value)
 * @method static Builder|self whereModelType($value)
 * @method static Builder|self whereUpdatedAt($value)
 * @mixin Eloquent
 */
class MetaTag extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function model(): MorphTo
    {
        return $this->morphTo();
    }
}
