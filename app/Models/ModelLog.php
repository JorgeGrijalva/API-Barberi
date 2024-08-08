<?php
declare(strict_types=1);

namespace App\Models;

use App\Traits\UserSearch;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Model;
use Str;

/**
 * App\Models\ModelLog
 *
 * @property int $id
 * @property string $model_type
 * @property int $model_id
 * @property array $data
 * @property string $type
 * @property int $created_by
 * @property Carbon|null $created_at
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self filter(array $filter)
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereId($value)
 * @mixin Eloquent
 * @property-read User|null $createdBy
 * @property-read Model|null $modelType
 */
class ModelLog extends Model
{
    use UserSearch;

    protected $guarded = ['id'];

    public $timestamps = false;

    protected $casts = [
        'data' => 'array',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function modelType(): BelongsTo
    {
        return $this->belongsTo($this->model_type, 'model_id', 'id');
    }

    public function scopeFilter(Builder $query, array $filter) {

        $query->when(data_get($filter, 'model_type'), function (Builder $q, $modelType) {

            $modelName = 'App\\Models\\' . Str::ucfirst($modelType);

            $q->where('model_type', $modelName);

        })->when(data_get($filter, 'model_id'), function (Builder $q, $modelId) {

            $q->where('model_id', $modelId);

        })->when(data_get($filter, 'type') && data_get($filter, 'model_type'), function (Builder $q) use ($filter) {

            $q->where('type', data_get($filter, 'model_type') . '_' . data_get($filter, 'type'));

        })->when(data_get($filter, 'user_id'), function (Builder $q, $userId) {

            $q->where('created_by', $userId);

        })->when(data_get($filter, 'search'), function ($q, $search) {
            $q->whereHas('createdBy', $this->search($q, $search));
        });

    }
}
