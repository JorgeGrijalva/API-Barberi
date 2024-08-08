<?php
declare(strict_types=1);

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Database\Factories\UserTranslationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * App\Models\UserTranslation
 *
 * @property int $id
 * @property int $user_id
 * @property string $locale
 * @property string $title
 * @property string|null $description
 * @method static UserTranslationFactory factory(...$parameters)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereUserId($value)
 * @method static Builder|self whereDescription($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereLocale($value)
 * @method static Builder|self whereTitle($value)
 * @mixin Eloquent
 */

class UserTranslation extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public $timestamps = false;
}
