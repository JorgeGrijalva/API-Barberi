<?php
declare(strict_types=1);

namespace App\Models;

use Database\Factories\BlogTranslationFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\BlogTranslation
 *
 * @property int $id
 * @property int $blog_id
 * @property string $locale
 * @property string $title
 * @property string|null $short_desc
 * @property string|null $description
 * @method static BlogTranslationFactory factory(...$parameters)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereBlogId($value)
 * @method static Builder|self whereDescription($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereLocale($value)
 * @method static Builder|self whereShortDesc($value)
 * @method static Builder|self whereTitle($value)
 * @mixin Eloquent
 */
class BlogTranslation extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $guarded = ['id'];
}
