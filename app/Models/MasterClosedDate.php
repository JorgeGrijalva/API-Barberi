<?php
declare(strict_types=1);

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * App\Models\MasterClosedDate
 *
 * @property int $id
 * @property int $master_id
 * @property string|null $date
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property User|null $master
 * @method static Builder|self filter(array $filter)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereUpdatedAt($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereMasterId($value)
 * @mixin Eloquent
 */
class MasterClosedDate extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function master(): BelongsTo
    {
        return $this->belongsTo(User::class, 'master_id');
    }

    public function scopeFilter($query, array $filter) {
        $query
            ->when(data_get($filter, 'master_id'),  fn($q, $masterId)  => $q->where('master_id', $masterId))
            ->when(data_get($filter, 'master_ids'), fn($q, $masterIds) => $q->whereIn('master_id', $masterIds))
            ->when(data_get($filter, 'date_from'),  fn($q, $dateFrom)  => $q->where('date', '>=', $dateFrom))
            ->when(data_get($filter, 'date_to'),    fn($q, $dateTo)    => $q->where('date', '<=', $dateTo))
            ->when(data_get($filter, 'invite_status'), function ($q) {
                $q->whereHas('master', function ($q) {
                    $q->whereHas('invitations', function ($q) {
                        $q->where('status', Invitation::ACCEPTED);
                    });
                });
            });
    }
}
