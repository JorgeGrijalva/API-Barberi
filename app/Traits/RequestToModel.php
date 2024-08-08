<?php
declare(strict_types=1);

namespace App\Traits;

use App\Models\RequestModel;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Collection;

/**
 * @property Collection|RequestModel[] $models
 * @property RequestModel|null $model
 * @property int $models_count
 */
trait RequestToModel
{
    public function models(): MorphMany
    {
        return $this->morphMany(RequestModel::class, 'model');
    }

    public function model(): MorphOne
    {
        return $this->morphOne(RequestModel::class, 'model');
    }
}
