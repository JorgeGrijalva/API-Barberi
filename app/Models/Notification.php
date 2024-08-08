<?php
declare(strict_types=1);

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * App\Models\Notification
 *
 * @property int $id
 * @property string $type
 * @property array $payload
 * @property boolean $active - Внутри таблицы notification_user
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereUpdatedAt($value)
 * @mixin Eloquent
 */
class Notification extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'payload'       => 'array',
        'created_at'    => 'datetime:Y-m-d H:i:s',
        'updated_at'    => 'datetime:Y-m-d H:i:s',
    ];

    public const PUSH = 'push';

    public const TYPES = [
        self::PUSH => self::PUSH,
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, NotificationUser::class)
            ->as('notification')
            ->withPivot('active');
    }
}
