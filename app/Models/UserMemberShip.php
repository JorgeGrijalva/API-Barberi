<?php
declare(strict_types=1);

namespace App\Models;

use App\Traits\Payable;
use Carbon\Carbon;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * App\Models\UserMemberShip
 *
 * @property int $id
 * @property int|null $member_ship_id
 * @property int|null $user_id
 * @property string|null $color
 * @property int|null $price
 * @property int|null $expired_at
 * @property int|null $sessions
 * @property int|null $sessions_count
 * @property int|null $remainder
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property User|null $user
 * @property MemberShip|null $memberShip
 * @property Collection|MemberShipService[] $memberShipServices
 * @method static Builder|self filter(array $filter)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereId($value)
 * @mixin Eloquent
 */
class UserMemberShip extends Model
{
    use Payable;

    protected $table = 'user_member_ships';

    public $guarded = ['id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function memberShipServices(): HasMany
    {
        return $this->hasMany(MemberShipService::class, 'member_ship_id', 'member_ship_id');
    }

    public function memberShip(): BelongsTo
    {
        return $this->belongsTo(MemberShip::class);
    }

    public function scopeFilter($query, array $filter): void
    {
        $query
            ->when(data_get($filter, 'shop_id'), function ($q, $id) {
                $q->whereHas('memberShip', fn($q) => $q->where('shop_id', $id));
            })
            ->when(data_get($filter, 'shop_ids'), function ($q, $ids) {
                $q->whereHas('memberShip', fn($q) => $q->whereIn('shop_id', (array)$ids));
            })
            ->when(data_get($filter, 'service_id'), function ($q, $id) {
                $q->whereHas('memberShipServices', fn($q) => $q->where('service_id', $id));
            })
            ->when(data_get($filter, 'service_ids'), function ($q, $ids) {
                $q->whereHas('memberShipServices', fn($q) => $q->whereIn('service_id', (array)$ids));
            })
            ->when(isset($filter['member_ship_id']), fn($q) => $q->where('member_ship_id', $filter['member_ship_id']))
            ->when(isset($filter['user_id']),        fn($q) => $q->where('user_id',        $filter['user_id']))
            ->when(isset($filter['color']),          fn($q) => $q->where('color',          $filter['color']))
            ->when(isset($filter['price']),          fn($q) => $q->where('price',          $filter['price']))
            ->when(isset($filter['date_from']),      fn($q) => $q->whereDate('expired_at', '>=', $filter['date_from']))
            ->when(isset($filter['date_to']),        fn($q) => $q->whereDate('expired_at', '<=', $filter['date_to']))
            ->when(isset($filter['sessions']),       fn($q) => $q->where('sessions',       $filter['sessions']))
            ->when(isset($filter['sessions_count']), fn($q) => $q->where('sessions_count', $filter['sessions_count']))
            ->when(isset($filter['remainder']),      fn($q) => $q->where('remainder',      $filter['remainder']));
    }
}
