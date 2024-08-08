<?php
declare(strict_types=1);

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\ShopTag
 *
 * @property int $shop_tag_id
 * @property int $shop_id
 * @property Shop|null $shop
 * @property ShopTag|null $shop_tag
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereShopId($value)
 * @method static Builder|self whereShopTagId($value)
 * @mixin Eloquent
 */
class AssignShopTag extends Model
{
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function shopTag(): BelongsTo
    {
        return $this->belongsTo(ShopTag::class);
    }
}
