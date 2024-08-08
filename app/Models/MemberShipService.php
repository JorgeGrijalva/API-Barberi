<?php
declare(strict_types=1);

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\MemberShipService
 *
 * @property string|null $member_ship_id
 * @property double|null $service_id
 * @method static Builder|self filter(array $filter)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereId($value)
 * @mixin Eloquent
 */
class MemberShipService extends Model
{
    protected $table = 'member_ship_services';
    public    $timestamps = false;
    protected $fillable = [
        'member_ship_id',
        'service_id',
    ];

    public function memberShip(): BelongsTo
    {
        return $this->belongsTo(MemberShip::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function scopeFilter($query, array $filter): void
    {
        $query
            ->when(isset($filter['member_ship_id']), fn($q) => $q->where('member_ship_id', $filter['member_ship_id']))
            ->when(isset($filter['service_id']),     fn($q) => $q->where('service_id',     $filter['service_id']));
    }
}
