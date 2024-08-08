<?php
declare(strict_types=1);

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
/**
 * App\Models\GiftCartTranslation
 *
 * @property int $id
 * @property int $gift_cart_id
 * @property string $locale
 * @property string $title
 * @property string|null $description
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereDescription($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereLocale($value)
 * @method static Builder|self whereGiftCartId($value)
 * @method static Builder|self whereTitle($value)
 * @mixin Eloquent
 */
class GiftCartTranslation extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $guarded = ['id'];
}
