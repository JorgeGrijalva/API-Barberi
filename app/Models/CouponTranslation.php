<?php
declare(strict_types=1);

namespace App\Models;

use Database\Factories\CouponTranslationFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\CouponTranslation
 *
 * @property int $id
 * @property int $coupon_id
 * @property string $locale
 * @property string $title
 * @property string|null $description
 * @method static CouponTranslationFactory factory(...$parameters)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereCouponId($value)
 * @method static Builder|self whereDescription($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereLocale($value)
 * @method static Builder|self whereTitle($value)
 * @mixin Eloquent
 */
class CouponTranslation extends Model
{
    use HasFactory;

    public $timestamps = false;

    public $guarded = ['id'];
}
