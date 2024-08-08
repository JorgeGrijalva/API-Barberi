<?php
declare(strict_types=1);

namespace App\Models;

use Database\Factories\BannerTranslationFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\BannerTranslation
 *
 * @property int $id
 * @property int $banner_id
 * @property string $locale
 * @property string $title
 * @property string|null $description
 * @property string|null $button_text
 * @method static BannerTranslationFactory factory(...$parameters)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereBannerId($value)
 * @method static Builder|self whereButtonText($value)
 * @method static Builder|self whereDescription($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereLocale($value)
 * @method static Builder|self whereTitle($value)
 * @mixin Eloquent
 */
class BannerTranslation extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public $timestamps = false;
}
