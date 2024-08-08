<?php
declare(strict_types=1);

namespace App\Models;

use App\Traits\Loadable;
use Carbon\Carbon;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

/**
 * App\Models\MemberShip
 *
 * @property int $id
 * @property int|null $shop_id
 * @property string|null $color
 * @property double|null $price
 * @property string|null $time
 * @property int|null $sessions
 * @property int|null $sessions_count
 * @property boolean $active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Shop|null $shop
 * @property Collection|MemberShipTranslation[] $translations
 * @property MemberShipTranslation|null $translation
 * @property int|null $translations_count
 * @property Collection|MemberShipService[] $memberShipServices
 * @property MemberShipService|null $memberShipService
 * @property int|null $member_ship_services_count
 * @method static Builder|self filter(array $filter)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereId($value)
 * @mixin Eloquent|Builder|self
 */
class MemberShip extends Model
{
    use Loadable;

    public $guarded = ['id'];
    protected $table = 'member_ships';

    protected $casts = [
        'active' => 'bool'
    ];

    const TIMES = [
        '1 day',
        '3 days',
        '7 days',
        '14 days',
        '1 month',
        '2 month',
        '3 month',
        '4 month',
        '5 month',
        '6 month',
        '8 month',
        '1 year',
        '18 months',
        '2 years',
        '5 years',
    ];

    const LIMITED   = 1;
    const UNLIMITED = 2;

    const SESSIONS = [
        self::LIMITED   => 'limited',
        self::UNLIMITED => 'unlimited',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(MemberShipTranslation::class);
    }

    public function translation(): HasOne
    {
        return $this->hasOne(MemberShipTranslation::class);
    }

    public function memberShipService(): HasOne
    {
        return $this->hasOne(MemberShipService::class);
    }

    public function memberShipServices(): HasMany
    {
        return $this->hasMany(MemberShipService::class);
    }

    public function memberShipUsers(): HasMany
    {
        return $this->hasMany(UserMemberShip::class);
    }

    public function scopeFilter($query, array $filter): void
    {
        $query
            ->when(isset($filter['shop_id']),        fn($q) => $q->where('shop_id',        $filter['shop_id']))
            ->when(isset($filter['active']),         fn($q) => $q->where('active',         $filter['active']))
            ->when(isset($filter['color']),          fn($q) => $q->where('color',          $filter['color']))
            ->when(isset($filter['price']),          fn($q) => $q->where('price',          $filter['price']))
            ->when(isset($filter['time']),           fn($q) => $q->where('time',           $filter['time']))
            ->when(isset($filter['sessions']),       fn($q) => $q->where('sessions',       $filter['sessions']))
            ->when(isset($filter['sessions_count']), fn($q) => $q->where('sessions_count', $filter['sessions_count']))
            ->when(isset($filter['service_id']), function ($q) use ($filter) {
                $q->whereHas('memberShipServices', fn($q) => $q->where('service_id', $filter['service_id']));
            })
            ->when(isset($filter['service_ids']) && is_array($filter['service_ids']), function ($q) use ($filter) {
                $q->whereHas('memberShipServices', fn($q) => $q->whereIn('service_id', $filter['service_ids']));
            })
            ->when(data_get($filter, 'search'), function ($query, $search) {
                $query->whereHas('translations', function ($q) use ($search) {
                    $q
                        ->where(fn($q) => $q->where('title', 'LIKE', "%$search%")->orWhere('id', $search))
                        ->select('id', 'member_ship_id', 'locale', 'title');
                });
            });
    }
}
