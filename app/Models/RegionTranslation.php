<?php
declare(strict_types=1);

namespace App\Models;

use App\Traits\Regions;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * App\Models\RegionTranslation
 *
 * @property int $id
 * @property int $region_id
 * @property string $locale
 * @property string $title
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereRegionId($value)
 * @method static Builder|self whereTitle($value)
 * @mixin Eloquent
 */
class RegionTranslation extends Model
{
    use Regions;

    public $timestamps = false;

    protected $guarded = ['id'];
}
