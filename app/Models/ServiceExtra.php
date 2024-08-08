<?php
declare(strict_types=1);

namespace App\Models;

use App\Traits\Loadable;
use Carbon\Carbon;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

/**
 * App\Models\ServiceExtra
 * @property int $id
 * @property int $service_id
 * @property int $shop_id
 * @property boolean $active
 * @property double $price
 * @property string $img
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ServiceExtraTranslation $translation
 * @property-read Collection|ServiceExtraTranslation[] $translations
 * @property-read Service $service
 * @property-read Shop $shop
 * @method static Builder|self filter(array $filter)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereId($value)
 * @mixin Eloquent
 */
class ServiceExtra extends Model
{
    use HasFactory, Loadable;

    protected $guarded = ['id'];

    protected $casts = [
        'active' => 'boolean',
        'img'    => 'string'
    ];

    public function translations(): HasMany
    {
        return $this->hasMany(ServiceExtraTranslation::class);
    }

    public function translation(): HasOne
    {
        return $this->hasOne(ServiceExtraTranslation::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function scopeFilter($query, array $filter)
    {

        if (isset($filter['empty_shop'])) {
            unset($filter['shop_id']);
            unset($filter['shop_ids']);
        }

        $query
            ->when(data_get($filter, 'service_id'), fn($q, $serviceId)  => $q->where('service_id', $serviceId))
            ->when(data_get($filter, 'empty_shop'), fn($q)              => $q->whereNull('shop_id'))
            ->when(data_get($filter, 'shop_id'),    fn($q, $shopId)     => $q->where('shop_id',    $shopId))
            ->when(data_get($filter, 'shop_ids'),   fn($q, $shopIds)    => $q->whereIn('shop_id',  $shopIds))
            ->when(isset($filter['active']),            fn($q)              => $q->where('active', $filter['active']))
            ->when(data_get($filter, 'search'), function ($q, $search) {
                $q->whereHas('translation', fn($q) => $q->where('title', 'LIKE', "%$search%"));
            });
    }
}
