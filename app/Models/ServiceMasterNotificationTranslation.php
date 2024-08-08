<?php
declare(strict_types=1);

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\ServiceMasterNotificationTranslation
 *
 * @property int $id
 * @property int $notify_id
 * @property string $locale
 * @property string $title
 * @property ServiceMasterNotification|null $serviceMasterNotification
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereTitle($value)
 * @mixin Eloquent
 */
class ServiceMasterNotificationTranslation extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    public function serviceMasterNotification(): BelongsTo
    {
        return $this->belongsTo(ServiceMasterNotification::class);
    }

}
