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
 * App\Models\BackupHistory
 *
 * @property int $id
 * @property string $title
 * @property int $status
 * @property string|null $path
 * @property int $created_by
 * @property Carbon|null $created_at
 * @property-read User $user
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereCreatedBy($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self wherePath($value)
 * @method static Builder|self whereStatus($value)
 * @method static Builder|self whereTitle($value)
 * @mixin Eloquent
 */
class BackupHistory extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'status' => 'bool',
    ];

    public $timestamps = false;

    public function getDates(): array
    {
        return ['created_at'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

}
