<?php
declare(strict_types=1);

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * App\Models\ServiceMasterNotification
 *
 * @property int $id
 * @property int $service_master_id
 * @property int $notification_time
 * @property string $notification_type
 * @property string $last_sent_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property ServiceMaster $serviceMaster
 * @property Collection|ServiceMasterNotificationTranslation[] $translations
 * @property ServiceMasterNotificationTranslation|null $translation
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereUpdatedAt($value)
 * @mixin Eloquent
 */
class ServiceMasterNotification extends Model
{
    protected $guarded = ['id'];

    const DAY  = 'day';
    const WEEK = 'week';

    const NOTIFICATION_TYPES = [
        self::DAY  => self::DAY,
        self::WEEK => self::WEEK
    ];

    public function translation(): HasOne
    {
        return $this->hasOne(ServiceMasterNotificationTranslation::class, 'notify_id');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(ServiceMasterNotificationTranslation::class, 'notify_id');
    }

    public function serviceMaster(): BelongsTo
    {
        return $this->belongsTo(ServiceMaster::class);
    }

    public function scopeFilter($query, array $filter)
    {
        $query
            ->when(data_get($filter, 'service_master_id'), fn($q, $serviceMasterId) => $q
                ->where('service_master_id', $serviceMasterId))
            ->when(data_get($filter, 'notification_time'), fn($q, $notificationTime) => $q
                ->where('notification_time', $notificationTime))
            ->when(data_get($filter, 'notification_type'), fn($q, $notificationType) => $q
                ->where('notification_type', $notificationType))
            ->when(data_get($filter, 'shop_id'), function ($q) use ($filter) {
                $q->whereHas('serviceMaster', function ($q) use ($filter) {
                    $q->where('shop_id', data_get($filter, 'shop_id'));
                });
            })->when(data_get($filter, 'master_id'), function ($q) use ($filter) {
                $q->whereHas('serviceMaster', function ($q) use ($filter) {
                    $q->where('master_id', data_get($filter, 'master_id'));
                });
            });
    }
}
