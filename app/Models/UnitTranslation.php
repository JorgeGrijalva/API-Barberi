<?php
declare(strict_types=1);

namespace App\Models;

use Database\Factories\UnitTranslationFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\UnitTranslation
 *
 * @property int $id
 * @property int $unit_id
 * @property string $locale
 * @property string $title
 * @method static UnitTranslationFactory factory(...$parameters)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereLocale($value)
 * @method static Builder|self whereTitle($value)
 * @method static Builder|self whereUnitId($value)
 * @mixin Eloquent
 */
class UnitTranslation extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public $timestamps = false;
}
