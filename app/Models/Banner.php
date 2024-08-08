<?php
declare(strict_types=1);

namespace App\Models;

use App\Traits\Likable;
use App\Traits\Loadable;
use Database\Factories\BannerFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * App\Models\Banner
 *
 * @property int $id
 * @property string|null $url
 * @property string|null $img
 * @property boolean $active
 * @property string $type
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property bool $clickable
 * @property int $input
 * @property int $shop_id
 * @property-read Shop|null $shop
 * @property-read Collection|Gallery[] $galleries
 * @property-read int|null $galleries_count
 * @property-read Collection|Like[] $likes
 * @property-read int|null $likes_count
 * @property-read Collection|Product[] $products
 * @property-read int|null $products_count
 * @property-read BannerTranslation|null $translation
 * @property-read Collection|BannerTranslation[] $translations
 * @property-read int|null $translations_count
 * @method static BannerFactory factory(...$parameters)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereActive($value)
 * @method static Builder|self whereClickable($value)
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereImg($value)
 * @method static Builder|self whereUpdatedAt($value)
 * @method static Builder|self whereUrl($value)
 * @mixin Eloquent
 */
class Banner extends Model
{
    use HasFactory, Loadable, Likable;

    protected $guarded = ['id'];

    protected $casts = [
        'active'    => 'bool',
        'clickable' => 'bool'
    ];

    const BANNER = 'banner';
    const LOOK   = 'look';

    const TYPES = [
        self::BANNER,
        self::LOOK,
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, BannerProduct::class);
    }

    // Translations
    public function translations(): HasMany
    {
        return $this->hasMany(BannerTranslation::class);
    }

    public function translation(): HasOne
    {
        return $this->hasOne(BannerTranslation::class);
    }
}
