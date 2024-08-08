<?php
declare(strict_types=1);

namespace App\Models;

use App\Traits\Loadable;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * App\Models\Referral
 *
 * @property int $id
 * @property double $price_from
 * @property double $price_to
 * @property Carbon|null $expired_at
 * @property string $img
 * @property Translation|null $translation
 * @property Collection|Translation[] $translations
 * @property int $translations_count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereUpdatedAt($value)
 * @mixin Eloquent
 */
class Referral extends Model
{
    use Loadable;

    protected $guarded = ['id'];

    // Translations
    public function translations(): HasMany
    {
        return $this->hasMany(ReferralTranslation::class);
    }

    public function translation(): HasOne
    {
        return $this->hasOne(ReferralTranslation::class);
    }
}
