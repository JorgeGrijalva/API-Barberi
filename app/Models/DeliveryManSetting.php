<?php
declare(strict_types=1);

namespace App\Models;

use App\Traits\Areas;
use App\Traits\Cities;
use App\Traits\Countries;
use App\Traits\Loadable;
use App\Traits\Regions;
use Database\Factories\DeliveryManSettingFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Schema;

/**
 * App\Models\DeliveryManSetting
 *
 * @property int $id
 * @property int $user_id
 * @property string $type_of_technique
 * @property string $brand
 * @property string $model
 * @property string $number
 * @property string $color
 * @property boolean $online
 * @property array $location
 * @property integer|null $width
 * @property integer|null $height
 * @property integer|null $length
 * @property integer|null $kg
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $deliveryman
 * @property-read Collection|Gallery[] $galleries
 * @property-read int|null $galleries_count
 * @method static DeliveryManSettingFactory factory(...$parameters)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self filter(array $filter)
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereUserId($value)
 * @method static Builder|self whereTypeOfTechnique($value)
 * @method static Builder|self whereBrand($value)
 * @method static Builder|self whereModel($value)
 * @method static Builder|self whereNumber($value)
 * @method static Builder|self whereColor($value)
 * @method static Builder|self whereOnline($value)
 * @mixin Eloquent
 */
class DeliveryManSetting extends Model
{
    use HasFactory, Loadable, Regions, Countries, Cities, Areas;

    protected $guarded  = ['id'];

    protected $table    = 'deliveryman_settings';

    protected $casts    = [
        'location'  => 'array',
        'online'    => 'bool',
    ];

    const BENZINE       = 'benzine';
    const ELECTRIC      = 'electric';
    const DIESEL        = 'diesel';
    const GAS           = 'gas';
    const MOTORBIKE     = 'motorbike';
    const BIKE          = 'bike';
    const FOOT          = 'foot';
    const HYBRID        = 'hybrid';

    const TYPE_OF_TECHNIQUES = [
        self::BENZINE       => self::BENZINE,
        self::DIESEL        => self::DIESEL,
        self::ELECTRIC      => self::ELECTRIC,
        self::GAS           => self::GAS,
        self::MOTORBIKE     => self::MOTORBIKE,
        self::BIKE          => self::BIKE,
        self::FOOT          => self::FOOT,
        self::HYBRID        => self::HYBRID,
    ];

    public function deliveryman(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeFilter($query, array $filter) {

        $column = data_get($filter, 'column','id');

        if ($column !== 'id') {
            $column = Schema::hasColumn('deliveryman_settings', $column) ? $column : 'id';
        }

        $query
            ->when(data_get($filter, 'shop_id'), function (Builder $q, $shopId) {
                $q->whereHas('deliveryman.invite', fn($q) => $q->where('shop_id', $shopId));
            })
            ->when(data_get($filter, 'region_id'),  fn($q,  $regionId)  => $q->where('region_id',  $regionId))
            ->when(data_get($filter, 'country_id'), fn($q,  $countryId) => $q->where('country_id', $countryId))
            ->when(data_get($filter, 'city_id'),    fn($q,  $cityId)    => $q->where('city_id',    $cityId))
            ->when(data_get($filter, 'area_id'),    fn($q,  $areaId)    => $q->where('area_id',    $areaId))
            ->orderBy($column, $filter['sort'] ?? 'desc');
    }
}
