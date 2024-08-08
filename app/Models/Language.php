<?php
declare(strict_types=1);

namespace App\Models;

use App\Traits\Loadable;
use Database\Factories\LanguageFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Language
 *
 * @property int $id
 * @property string|null $title
 * @property string $locale
 * @property int $backward
 * @property int $default
 * @property boolean $active
 * @property string|null $img
 * @property-read Collection|Gallery[] $galleries
 * @property-read int|null $galleries_count
 * @method static LanguageFactory factory(...$parameters)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereActive($value)
 * @method static Builder|self whereBackward($value)
 * @method static Builder|self whereDefault($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereImg($value)
 * @method static Builder|self whereLocale($value)
 * @method static Builder|self whereTitle($value)
 * @mixin Eloquent
 */
class Language extends Model
{
    use HasFactory, Loadable;

    protected $guarded = ['id'];

    public $timestamps = false;

    protected $casts = [
        'backward'  => 'bool',
        'default'   => 'bool',
        'active'    => 'bool'
    ];
}
