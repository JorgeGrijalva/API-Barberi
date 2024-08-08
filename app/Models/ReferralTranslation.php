<?php
declare(strict_types=1);

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * App\Models\ReferralTranslation
 *
 * @property int $id
 * @property int $referral_id
 * @property string $locale
 * @property string $title
 * @property string|null $description
 * @property string|null $faq
 * @property Referral|null $referral
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereDescription($value)
 * @method static Builder|self whereFaq($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereLocale($value)
 * @method static Builder|self whereReferralId($value)
 * @method static Builder|self whereTitle($value)
 * @mixin Eloquent
 */
class ReferralTranslation extends Model
{
    protected $guarded = ['id'];

}
