<?php
declare(strict_types=1);

namespace App\Models;

use App\Traits\Loadable;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * App\Models\ShopTag
 *
 * @property int $id
 * @property int $img
 * @property Collection|Gallery[] $galleries
 * @property int $galleries_count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ShopTagTranslation|null $translation
 * @property-read Collection|ShopTagTranslation[] $translations
 * @property-read int|null $translations_count
 * @property-read Collection|ShopTagTranslation[] $assignShopTags
 * @property-read int|null $assign_shop_tags_count
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereUpdatedAt($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereShopId($value)
 * @mixin Eloquent
 */
class ShopTag extends Model
{
    use Loadable;

    protected $guarded = ['id'];

    // Translations
    public function translations(): HasMany
    {
        return $this->hasMany(ShopTagTranslation::class);
    }

    public function translation(): HasOne
    {
        return $this->hasOne(ShopTagTranslation::class);
    }

    public function assignShopTags(): HasMany
    {
        return $this->hasMany(AssignShopTag::class);
    }
}
