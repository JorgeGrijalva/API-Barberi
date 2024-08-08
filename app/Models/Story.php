<?php
declare(strict_types=1);

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Schema;

/**
 * App\Models\Story
 *
 * @property int $id
 * @property array $file_urls
 * @property int $product_id
 * @property int $shop_id
 * @property boolean $active
 * @property Product|null $product
 * @property Shop|null $shop
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self filter(array $filter = [])
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereProductId($value)
 * @method static Builder|self whereUpdatedAt($value)
 * @mixin Eloquent
 */
class Story extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'active'        => 'boolean',
        'file_urls'     => 'array',
        'created_at'    => 'datetime:Y-m-d H:i:s',
        'updated_at'    => 'datetime:Y-m-d H:i:s',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function scopeFilter($query, array $filter = []) {

        $column = $filter['column'] ?? 'id';

        if ($column !== 'id') {
            $column = Schema::hasColumn('stories', $column) ? $column : 'id';
        }

        $query
            ->when(data_get($filter, 'shop_id'),    fn($q, $shopId)     => $q->where('shop_id', $shopId))
            ->when(data_get($filter, 'product_id'), fn($q, $productId)  => $q->where('product_id', $productId))
            ->when(isset($filter['active']), fn($q) => $q->where('active', $filter['active']))
            ->orderBy($column, $filter['sort'] ?? 'desc');
    }
}
