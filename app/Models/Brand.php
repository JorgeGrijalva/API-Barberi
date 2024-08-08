<?php
declare(strict_types=1);

namespace App\Models;

use App\Traits\Loadable;
use App\Traits\MetaTagable;
use Database\Factories\BrandFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;
use Schema;

/**
 * App\Models\Brand
 *
 * @property int $id
 * @property string $slug
 * @property string $uuid
 * @property string $title
 * @property int $active
 * @property string|null $img
 * @property string|null $shop_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Shop|null $shop
 * @property-read Collection|Gallery[] $galleries
 * @property-read int|null $galleries_count
 * @property-read Collection|Product[] $products
 * @property-read int|null $products_count
 * @property-read Collection|ModelLog[] $logs
 * @property-read int|null $logs_count
 * @method static BrandFactory factory(...$parameters)
 * @method static Builder|self filter($array)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self updatedDate($updatedDate)
 * @method static Builder|self whereActive($value)
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereImg($value)
 * @method static Builder|self whereShopId($value)
 * @method static Builder|self whereTitle($value)
 * @method static Builder|self whereUpdatedAt($value)
 * @method static Builder|self whereUuid($value)
 * @mixin Eloquent
 */
class Brand extends Model
{
    use HasFactory, Loadable, MetaTagable;

    protected $guarded = ['id'];

    protected $casts = [
        'active' => 'bool',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function logs(): MorphMany
    {
        return $this->morphMany(ModelLog::class, 'model');
    }

    public function scopeUpdatedDate($query, $updatedDate)
    {
        return $query->where('updated_at', '>', $updatedDate);
    }

    /* Filter Scope */
    public function scopeFilter($value, $filter)
    {
        $column = data_get($filter, 'column','id');

        if ($column !== 'id') {
            $column = Schema::hasColumn('brands', $column) ? $column : 'id';
        }

        return $value
            ->when(data_get($filter, 'search'), fn($q, $search) => $q->where('title', 'LIKE', "%$search%"))
            ->when(data_get($filter, 'slug'), fn($q, $slug) => $q->where('slug', $slug))
            ->when(data_get($filter, 'shop_id'), function ($q, $shopId) use ($filter) {

                $q->where(function ($q) use ($filter, $shopId) {

                    $q->where('shop_id', $shopId);

                    if (!isset($filter['is_admin'])) {
                        $q->orWhereNull('shop_id');
                    }

                });

            })
            ->when(isset($filter['active']), function ($q) use ($filter) {
                $q->whereActive($filter['active']);
            })
            ->when(data_get($filter, 'category_id'), function ($q, $categoryId) {
                $q->whereHas('products', fn($q) => $q
                    ->whereHas('stocks', fn($q) => $q->where('quantity', '>', 0))
                    ->where('active', true)
                    ->where('status', Product::PUBLISHED)
                    ->where('category_id', $categoryId)
                );
            })
            ->orderBy($column, $filter['sort'] ?? 'desc');
    }

}
