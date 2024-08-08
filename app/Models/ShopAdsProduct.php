<?php
declare(strict_types=1);

namespace App\Models;

use App\Traits\SetCurrency;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\ShopAdsProduct
 *
 * @property int $id
 * @property double $shop_ads_package_id
 * @property double $product_id
 * @property ShopAdsPackage|null $shopAdsPackage
 * @property Product|null $product
 * @method static Builder|self active()
 * @method static Builder|self filter(array $filter)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereId($value)
 * @mixin Eloquent
 */
class ShopAdsProduct extends Model
{
    use SetCurrency;

    public $guarded     = ['id'];
    public $timestamps  = false;

    public function shopAdsPackage(): BelongsTo
    {
        return $this->belongsTo(ShopAdsPackage::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeFilter($query, array $filter) {
        $query
            ->when(data_get($filter, 'shop_ads_package_id'), fn($q, $id) => $q->where('shop_ads_package_id', $id))
            ->when(data_get($filter, 'product_id'),          fn($q, $id) => $q->where('product_id', $id));
    }
}
