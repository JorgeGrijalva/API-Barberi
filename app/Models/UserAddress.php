<?php
declare(strict_types=1);

namespace App\Models;

use App\Traits\Loadable;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * App\Models\UserAddress
 *
 * @property int $id
 * @property string $title
 * @property int $user_id
 * @property array $address
 * @property array $location
 * @property bool $active
 * @property string $firstname
 * @property string $lastname
 * @property string $phone
 * @property string $zipcode
 * @property string $street_house_number
 * @property string $additional_details
 * @property int $region_id
 * @property int $country_id
 * @property int $city_id
 * @property int $area_id
 * @property User|null $user
 * @property Region|null $region
 * @property Country|null $country
 * @property City|null $city
 * @property Area|null $area
 * @property Order[]|Collection $orders
 * @property int $orders_count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder|self active()
 * @method static Builder|self filter(array $filter)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereId($value)
 * @mixin Eloquent
 */
class UserAddress extends Model
{
    use Loadable;

    public $guarded = ['id'];

    public $casts = [
        'address'   => 'array',
        'location'  => 'array',
        'active'    => 'bool',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'address_id');
    }

    public function scopeFilter($query, $filter) {
        $query
            ->when(data_get($filter, 'region_id'),  fn($q, $regionId)   => $q->where('region_id',  $regionId))
            ->when(data_get($filter, 'country_id'), fn($q, $countryId)  => $q->where('country_id', $countryId))
            ->when(data_get($filter, 'city_id'),    fn($q, $cityId)     => $q->where('city_id',    $cityId))
            ->when(data_get($filter, 'area_id'),    fn($q, $areaId)     => $q->where('area_id',    $areaId))
            ->when(data_get($filter, 'user_id'),    fn($q, $userId)     => $q->where('user_id',    $userId))
            ->when(data_get($filter, 'search'),     function ($query, $search) {
                $query
                    ->where(function ($q) use ($search) {
                        $q
                            ->where('title', 'LIKE', "%$search%")
                            ->orWhere('address', 'LIKE', "%$search%")
                            ->orWhere(function($query) use ($search) {

                                $firstNameLastName = explode(' ', $search);

                                if (data_get($firstNameLastName, 1)) {
                                    return $query
                                        ->where('firstname',  'LIKE', '%' . $firstNameLastName[0] . '%')
                                        ->orWhere('lastname',   'LIKE', '%' . $firstNameLastName[1] . '%');
                                }

                                return $query
                                    ->where('id', $search)
                                    ->orWhere('zipcode', $search)
                                    ->orWhere('firstname',           'LIKE', "%$search%")
                                    ->orWhere('lastname',            'LIKE', "%$search%")
                                    ->orWhere('phone',               'LIKE', "%$search%")
                                    ->orWhere('street_house_number', 'LIKE', "%$search%")
                                    ->orWhere('additional_details',  'LIKE', "%$search%");
                            });
                    });
            });
    }
}
