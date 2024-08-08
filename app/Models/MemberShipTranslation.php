<?php
declare(strict_types=1);

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\MemberShipTranslation
 *
 * @property int $id
 * @property int $member_ship_id
 * @property string $locale
 * @property string $title
 * @property string $description
 * @property string $term
 * @property MemberShip|null $memberShip
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereAreaId($value)
 * @method static Builder|self whereTitle($value)
 * @mixin Eloquent
 */
class MemberShipTranslation extends Model
{
    protected $table = 'member_ship_translations';

    public $timestamps = false;

    protected $guarded = ['id'];

    public function memberShip(): BelongsTo
    {
        return $this->belongsTo(MemberShip::class);
    }
}
