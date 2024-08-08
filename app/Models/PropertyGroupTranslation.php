<?php
declare(strict_types=1);

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\PropertyGroupTranslation
 *
 * @property int $id
 * @property int $extra_group_id
 * @property string $locale
 * @property string $title
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereExtraGroupId($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereLocale($value)
 * @method static Builder|self whereTitle($value)
 * @mixin Eloquent
 */
class PropertyGroupTranslation extends Model
{
    protected $guarded = ['id'];

    public $timestamps = false;
}
