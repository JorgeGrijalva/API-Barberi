<?php
declare(strict_types=1);

namespace App\Models;

use Database\Factories\ShopTranslationFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\ShopTranslation
 *
 * @property int $id
 * @property int $shop_id
 * @property string $locale
 * @property string $title
 * @property string|null $description
 * @property string|null $address
 * @method static ShopTranslationFactory factory(...$parameters)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereAddress($value)
 * @method static Builder|self whereDescription($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereLocale($value)
 * @method static Builder|self whereShopId($value)
 * @method static Builder|self whereTitle($value)
 * @mixin Eloquent
 */
class ShopTranslation extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $guarded = ['id'];
}
