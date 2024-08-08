<?php
declare(strict_types=1);

namespace App\Models;

use App\Traits\Loadable;
use App\Traits\SetCurrency;
use Database\Factories\DiscountFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Schema;

/**
 * App\Models\Discount
 *
 * @property int $id
 * @property int $shop_id
 * @property string $type
 * @property float $price
 * @property string $start
 * @property string|null $end
 * @property boolean $active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $img
 * @property-read Collection|Gallery[] $galleries
 * @property-read int|null $galleries_count
 * @property-read Collection|Stock[] $stocks
 * @property-read int|null $stocks_count
 * @method static DiscountFactory factory(...$parameters)
 * @method static Builder|self filter($filter)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereActive($value)
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereEnd($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereImg($value)
 * @method static Builder|self wherePrice($value)
 * @method static Builder|self whereShopId($value)
 * @method static Builder|self whereStart($value)
 * @method static Builder|self whereType($value)
 * @method static Builder|self whereUpdatedAt($value)
 * @mixin Eloquent
 */
class Discount extends Model
{
    use HasFactory, SetCurrency, Loadable;

    protected $guarded = ['id'];

    protected $casts = [
        'active' => 'bool',
    ];

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    /* Filter Scope */
    public function scopeFilter($query, array $filter)
    {
        $column = $filter['column'] ?? 'id';

        if ($column !== 'id') {
            $column = Schema::hasColumn('discounts', $column) ? $column : 'id';
        }

        return $query
            ->when(data_get($filter, 'type'),    fn($q, $type)   => $q->where('type', $type))
            ->when(data_get($filter, 'shop_id'), fn($q, $shopId) => $q->where('shop_id', $shopId))
            ->when(isset($filter['active']), fn($q) => $q->where('active', $filter['active']))
            ->orderBy($column, $filter['sort'] ?? 'desc');
    }}
