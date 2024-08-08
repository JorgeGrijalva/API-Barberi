<?php
declare(strict_types=1);

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

/**
 * App\Models\Region
 *
 * @property int $id
 * @property boolean $active
 * @property Country|null $country
 * @property Collection|Country[] $countries
 * @property City|null $city
 * @property Collection|City[] $cities
 * @property Area|null $area
 * @property Collection|Area[] $areas
 * @property Collection|RegionTranslation[] $translations
 * @property RegionTranslation|null $translation
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
class Region extends Model
{
    public $guarded     = ['id'];
    public $timestamps  = false;

    public $casts = [
        'active'    => 'bool',
    ];

    public function translations(): HasMany
    {
        return $this->hasMany(RegionTranslation::class);
    }

    public function translation(): HasOne
    {
        return $this->hasOne(RegionTranslation::class);
    }

    public function country(): HasOne
    {
        return $this->hasOne(Country::class);
    }

    public function countries(): HasMany
    {
        return $this->hasMany(Country::class);
    }

    public function city(): HasOne
    {
        return $this->hasOne(City::class);
    }

    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
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
        /** @var Region $query */
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
            ->when(isset($filter['active']), fn($q) => $q->where('active', $filter['active']))
            ->when(data_get($filter, 'has_price'), fn($q) => $q->whereHas('deliveryPrice'))
            ->when(data_get($filter, 'search'), function ($query, $search) {
                $query->whereHas('translations', function ($q) use ($search) {
                    $q
                        ->where(fn($q) => $q->where('title', 'LIKE', "%$search%")->orWhere('id', $search))
                        ->select('id', 'region_id', 'locale', 'title');
                });
            });
    }
}
