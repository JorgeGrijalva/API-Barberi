<?php
declare(strict_types=1);

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\BannerProduct
 *
 * @property int $id
 * @property int $product_id
 * @property int $banner_id
 * @property Banner $banner
 * @property Shop $shop
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereBannerId($value)
 * @method static Builder|self whereShopId($value)
 * @mixin Eloquent
 */
class BannerProduct extends Model
{
    protected $guarded = ['id'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function banner(): BelongsTo
    {
        return $this->belongsTo(Banner::class);
    }
}
