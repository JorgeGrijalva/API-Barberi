<?php
declare(strict_types=1);

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * App\Models\AdsPackageTranslation
 *
 * @property int $id
 * @property int $ads_package_id
 * @property string $locale
 * @property string $title
 * @property string $description
 * @property string $button_text
 * @property AdsPackage|null adsPackage
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereAreaId($value)
 * @method static Builder|self whereTitle($value)
 * @mixin Eloquent
 */
class AdsPackageTranslation extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    public function adsPackage(): BelongsTo
    {
        return $this->belongsTo(AdsPackage::class);
    }
}
