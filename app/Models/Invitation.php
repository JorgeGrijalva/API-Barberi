<?php
declare(strict_types=1);

namespace App\Models;

use Database\Factories\InvitationFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * App\Models\Invitation
 *
 * @property int $id
 * @property int $shop_id
 * @property int $user_id
 * @property int $created_by
 * @property string|null $role
 * @property int $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Shop $shop
 * @property-read User $user
 * @property-read User $createdBy
 * @method static InvitationFactory factory(...$parameters)
 * @method static Builder|self filter($filter)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereRole($value)
 * @method static Builder|self whereShopId($value)
 * @method static Builder|self whereStatus($value)
 * @method static Builder|self whereUpdatedAt($value)
 * @method static Builder|self whereUserId($value)
 * @method static Builder|self whereCreatedById($value)
 * @mixin Eloquent
 */
class Invitation extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    const NEW       = 1;
    const ACCEPTED  = 2;
    const REJECTED  = 4;
    const CANCELED  = 5;

    const STATUS = [
        'new'       => self::NEW,
        'accepted'  => self::ACCEPTED,
        'rejected'  => self::REJECTED,
        'canceled'  => self::CANCELED
    ];

    const STATUS_BY = [
        self::NEW       => 'new',
        self::ACCEPTED  => 'accepted',
        self::REJECTED  => 'rejected',
        self::CANCELED  => 'canceled'
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function getStatusKey($value)
    {
        foreach (self::STATUS as $index => $status) {
            if ($value == $status) {
                return $index;
            }
        }
    }

    public function scopeFilter($query, array $filter)
    {
        $query
            ->when(data_get($filter, 'status'), function ($q, $status) {
                $q->where('status', $status);
            })
            ->when(data_get($filter, 'user_id'), function ($q, $userId) {
                $q->where('user_id', $userId);
            })
            ->when(data_get($filter, 'created_by'), function ($q, $id) {
                $q->where('created_by', $id);
            })
            ->when(data_get($filter, 'shop_id'), function ($q, $shopId) {
                $q->where('shop_id', $shopId);
            });
    }
}
