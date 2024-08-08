<?php
declare(strict_types=1);

namespace App\Models;

use App\Traits\Cities;
use App\Traits\Countries;
use App\Traits\Regions;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * App\Models\Area
 *
 * @property int $id
 * @property boolean $active
 * @property int|null $region_id
 * @property int|null $country_id
 * @property int|null $city_id
 * @property Collection|AreaTranslation[] $translations
 * @property AreaTranslation|null $translation
 * @property int|null $translations_count
 * @property Collection|DeliveryPrice[] $deliveryPrices
 * @property DeliveryPrice|null $deliveryPrice
 * @property int|null $delivery_price_count
 * @method static Builder|self active()
 * @method static Builder|self filter(array $filter)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereId($value)
 * @mixin Eloquent
 */
class Area extends Model
{
    use Regions, Countries, Cities;

    public $guarded     = ['id'];
    public $timestamps  = false;

    public $casts = [
        'active' => 'bool',
    ];

    public function translations(): HasMany
    {
        return $this->hasMany(AreaTranslation::class);
    }

    public function translation(): HasOne
    {
        return $this->hasOne(AreaTranslation::class);
    }

    public function deliveryPrice(): HasOne
    {
        return $this->hasOne(DeliveryPrice::class);
    }

    public function deliveryPrices(): HasMany
    {
        return $this->hasMany(DeliveryPrice::class);
    }

    public function scopeActive($query): Builder
    {
        /** @var Area $query */
        return $query->where('active', true);
    }

    public function scopeFilter($query, array $filter): void
    {
        $query
            ->when(request()->is('api/v1/rest/*') && request('lang'), function ($q) {
                $q->whereHas('translation', fn($query) => $query->where(function ($q) {

                    $locale = Language::where('default', 1)->first()?->locale;

                    $q->where('locale', request('lang'))->orWhere('locale', $locale);

                }));
            })
            ->when(data_get($filter, 'region_id'),  fn($q, $regionId)   => $q->where('region_id',   $regionId))
            ->when(data_get($filter, 'country_id'), fn($q, $countryId)  => $q->where('country_id',  $countryId))
            ->when(data_get($filter, 'city_id'),    fn($q, $cityId)     => $q->where('city_id',     $cityId))
            ->when(data_get($filter, 'has_price'),  fn($q)              => $q->whereHas('deliveryPrice'))
            ->when(isset($filter['active']),            fn($q)              => $q->where('active', $filter['active']))
            ->when(data_get($filter, 'search'), function ($query, $search) {
                $query->whereHas('translations', function ($q) use ($search) {
                    $q
                        ->where(fn($q) => $q->where('title', 'LIKE', "%$search%")->orWhere('id', $search))
                        ->select('id', 'area_id', 'locale', 'title');
                });
            });
    }
}
