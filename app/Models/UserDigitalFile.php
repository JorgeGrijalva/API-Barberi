<?php
declare(strict_types=1);

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * App\Models\UserDigitalFile
 *
 * @property int $id
 * @property boolean $active
 * @property boolean $downloaded
 * @property int|null $digital_file_id
 * @property int|null $user_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property DigitalFile|null $digitalFile
 * @property User|null $user
 * @method static Builder|self active()
 * @method static Builder|self filter(array $filter)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereId($value)
 * @mixin Eloquent
 */
class UserDigitalFile extends Model
{
    public $guarded     = ['id'];
    public $timestamps  = false;

    public $casts = [
        'active'     => 'bool',
        'downloaded' => 'bool'
    ];

    public function digitalFile(): BelongsTo
    {
        return $this->belongsTo(DigitalFile::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query): Builder
    {
        /** @var Country $query */
        return $query->where('active', true);
    }

    public function scopeFilter($query, array $filter) {
        $query
            ->when(data_get($filter, 'digital_file_id'), fn($q, $id) => $q->where('digital_file_id', $id))
            ->when(data_get($filter, 'user_id'),         fn($q, $id) => $q->where('user_id', $id))
            ->when(isset($filter['active']), fn($q) => $q->where('active', $filter['active']));
    }
}
