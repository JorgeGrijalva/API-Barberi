<?php
declare(strict_types=1);

namespace App\Models;

use Database\Factories\TicketFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * App\Models\Ticket
 *
 * @property int $id
 * @property string $uuid
 * @property int $created_by
 * @property int|null $user_id
 * @property int|null $order_id
 * @property int $parent_id
 * @property string $type
 * @property string $subject
 * @property string $content
 * @property string $status
 * @property int $read
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection|Ticket[] $children
 * @property-read int|null $children_count
 * @method static TicketFactory factory(...$parameters)
 * @method static Builder|self filter($filter)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereContent($value)
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereCreatedBy($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereOrderId($value)
 * @method static Builder|self whereParentId($value)
 * @method static Builder|self whereRead($value)
 * @method static Builder|self whereStatus($value)
 * @method static Builder|self whereSubject($value)
 * @method static Builder|self whereType($value)
 * @method static Builder|self whereUpdatedAt($value)
 * @method static Builder|self whereUserId($value)
 * @method static Builder|self whereUuid($value)
 * @mixin Eloquent
 */
class Ticket extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'read' => 'bool',
    ];

    const OPEN      = 'open';
    const ANSWERED  = 'answered';
    const PROGRESS  = 'progress';
    const CLOSED    = 'closed';
    const REJECTED  = 'rejected';

    const STATUS = [
        self::OPEN      => self::OPEN,
        self::ANSWERED  => self::ANSWERED,
        self::PROGRESS  => self::PROGRESS,
        self::CLOSED    => self::CLOSED,
        self::REJECTED  => self::REJECTED,
    ];

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function scopeFilter($query, array $filter) {
        $query
            ->when(isset($filter['status']),             fn($q)              => $q->where('status', $filter['status']))
            ->when(data_get($filter, 'created_by'),  fn($q, $createdBy)  => $q->where('created_by', $createdBy))
            ->when(data_get($filter, 'user_id'),     fn($q, $userId)     => $q->where('user_id', $userId))
            ->when(data_get($filter, 'type'),        fn($q, $type)       => $q->where('type', $type));
    }
}
