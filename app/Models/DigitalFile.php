<?php
declare(strict_types=1);

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Schema;

/**
 * App\Models\DigitalFile
 *
 * @property int $id
 * @property boolean $active
 * @property int|null $product_id
 * @property string|null $path
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Product|null $product
 * @property User|null $userDigital
 * @property Collection|User[] $usersDigital
 * @property int|null $users_digital_count
 * @method static Builder|self active()
 * @method static Builder|self filter(array $filter)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereId($value)
 * @mixin Eloquent
 */
class DigitalFile extends Model
{
    public $guarded     = ['id'];
    public $timestamps  = false;

    public $casts = [
        'active' => 'bool',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function userDigital(): HasOne
    {
        return $this->hasOne(UserDigitalFile::class);
    }

    public function usersDigital(): HasMany
    {
        return $this->hasMany(UserDigitalFile::class);
    }

    public function scopeActive($query): Builder
    {
        /** @var DigitalFile $query */
        return $query->where('active', true);
    }

    public function scopeFilter($query, array $filter) {
        $column = $filter['column'] ?? 'id';

        if ($column !== 'id') {
            $column = Schema::hasColumn('digital_files', $column) ? $column : 'id';
        }

        $query
            ->when(data_get($filter, 'product_id'), fn($q, $productId) => $q->where('product_id', $productId))
            ->when(data_get($filter, 'shop_id'),    function ($query, $shopId) {
                $query->whereHas('product', fn($q) => $q->where('shop_id', $shopId));
            })
            ->when(data_get($filter, 'user_id'),    function ($query, $userId) {
                $query->whereHas('userDigital', fn($q) => $q->where('active', true)->where('user_id', $userId));
            })
            ->when(isset($filter['active']), fn($q) => $q->where('active', $filter['active']))
            ->orderBy($column, $filter['sort'] ?? 'desc');
    }
}
