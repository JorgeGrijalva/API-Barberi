<?php
declare(strict_types=1);

namespace App\Models;

use App\Traits\Countries;
use App\Traits\Regions;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

/**
 * App\Models\City
 *
 * @property int $id
 * @property int $region_id
 * @property int $country_id
 * @property boolean $active
 * @property Area|null $area
 * @property Collection|Area[] $areas
 * @property Collection|CityTranslation[] $translations
 * @property CityTranslation|null $translation
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
 * @method static Builder|self whereCategoryId($value)
 * @mixin Eloquent
 */
class City extends Model
{
    use Regions, Countries;

    public $guarded = ['id'];
    public $timestamps = false;

    public $casts = [
        'active'    => 'bool',
    ];

    public function translations(): HasMany
    {
        return $this->hasMany(CityTranslation::class);
    }

    public function translation(): HasOne
    {
        return $this->hasOne(CityTranslation::class);
    }

    public function area(): HasOne
    {
        return $this->hasOne(Area::class);
    }

    public function areas(): HasMany
    {
        return $this->hasMany(Area::class);
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
        /** @var City $query */
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
            ->when(data_get($filter, 'region_id'),  fn($q, $regionId) => $q->where('region_id', $regionId))
            ->when(data_get($filter, 'country_id'), fn($q, $countryId) => $q->where('country_id', $countryId))
            ->when(data_get($filter, 'has_price'),  fn($q) => $q->whereHas('deliveryPrice'))
            ->when(isset($filter['active']), fn($q) => $q->where('active', $filter['active']))
            ->when(data_get($filter, 'search'), function ($query, $search) {
                $query->whereHas('translations', function ($q) use ($search) {
                    $q
                        ->where(fn($q) => $q->where('title', 'LIKE', "%$search%")->orWhere('id', $search))
                        ->select('id', 'city_id', 'locale', 'title');
                });
            });
    }
}
