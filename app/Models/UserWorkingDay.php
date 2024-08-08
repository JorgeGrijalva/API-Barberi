<?php
declare(strict_types=1);

namespace App\Models;

use Database\Factories\UserWorkingDayFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Eloquent;

/**
 * App\Models\UserWorkingDay
 *
 * @property int $id
 * @property int $user_id
 * @property string $day
 * @property string|null $from
 * @property string|null $to
 * @property boolean|null $disabled
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property User|null $user
 * @method static UserWorkingDayFactory factory(...$parameters)
 * @method static Builder|self filter($filter = [])
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereUpdatedAt($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereUserId($value)
 * @method static Builder|self whereDay($value)
 * @method static Builder|self whereFrom($value)
 * @method static Builder|self whereTo($value)
 * @mixin Eloquent
 */
class UserWorkingDay extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'disabled' => 'bool',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeFilter($query, $filter = [])
    {
        return $query
            ->when(data_get($filter, 'day'),      fn($q, $day)      => $q->where('day', $day))
            ->when(data_get($filter, 'user_id'),  fn($q, $userId)   => $q->where('user_id', $userId))
            ->when(data_get($filter, 'from'),     fn($q, $from)     => $q->where('from', '>=', $from))
            ->when(data_get($filter, 'to'),       fn($q, $to)       => $q->where('to', '<=', $to))
            ->when(data_get($filter, 'disabled'), fn($q, $disabled) => $q->where('disabled', $disabled));
    }
}
