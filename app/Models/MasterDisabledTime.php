<?php
declare(strict_types=1);

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * App\Models\MasterDisabledTime
 *
 * @property int $id
 * @property int $master_id
 * @property string $repeats
 * @property string $custom_repeat_type
 * @property array $custom_repeat_value
 * @property string|null $date
 * @property string $from
 * @property string $to
 * @property string $end_type
 * @property string $end_value
 * @property boolean $can_booking
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property User $master
 * @property Collection|MasterDisabledTimeTranslation[] $translations
 * @property MasterDisabledTimeTranslation|null $translation
 * @property int|null $translations_count
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
class MasterDisabledTime extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    protected $casts = [
        'custom_repeat_value' => 'array',
        'can_booking'         => 'boolean',
    ];

    const DONT_REPEAT = 'dont_repeat';
    const DAY         = 'day';
    const WEEK        = 'week';
    const MONTH       = 'month';
    const CUSTOM      = 'custom';

    const NEVER       = 'never';
    const DATE        = 'date';
    const AFTER       = 'after';

    const REPEATS = [
        self::DONT_REPEAT => self::DONT_REPEAT,
        self::DAY         => self::DAY,
        self::WEEK        => self::WEEK,
        self::MONTH       => self::MONTH,
        self::CUSTOM      => self::CUSTOM,
    ];

    const CUSTOM_REPEAT_TYPE = [
        self::DAY   => self::DAY,
        self::WEEK  => self::WEEK,
        self::MONTH => self::MONTH,
    ];

    const END_TYPES = [
        self::NEVER => self::NEVER,
        self::DATE  => self::DATE,
        self::AFTER => self::AFTER,
    ];

    public function translations(): HasMany
    {
        return $this->hasMany(MasterDisabledTimeTranslation::class, 'disabled_time_id');
    }

    public function translation(): HasOne
    {
        return $this->hasOne(MasterDisabledTimeTranslation::class, 'disabled_time_id');
    }

    public function master(): BelongsTo
    {
        return $this->belongsTo(User::class, 'master_id');
    }

    public function scopeFilter($query, array $filter)
    {
        $query
            ->when(data_get($filter, 'master_id'), fn($q, $masterId) => $q->where('master_id', $masterId))
            ->when(data_get($filter, 'master_ids'), fn($q, $masterIds) => $q->whereIn('master_id', $masterIds))
            ->when(data_get($filter, 'invite_status'), function ($q) {
                $q->whereHas('master', function ($q) {
                    $q->whereHas('invitations', function ($q) {
                        $q->where('status', Invitation::ACCEPTED);
                    });
                });
            });
    }
}
