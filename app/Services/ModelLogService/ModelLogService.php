<?php
declare(strict_types=1);

namespace App\Services\ModelLogService;

use App\Models\ModelLog;
use App\Models\User;
use App\Services\CoreService;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class ModelLogService extends CoreService
{
    protected function getModelClass(): string
    {
        return ModelLog::class;
    }

    public function logging(Model|User $model, array $data, $type = 'logged'): void
    {
        try {

            if (count($data) > 0) {

                $modelClass = get_class($model);

                $modelName = explode('\\', $modelClass)[2] ?? 'model';

                ModelLog::create([
                    'model_type' => $modelClass,
                    'model_id'   => $model->id,
                    'data'       => $type === 'created' ? $data : $this->prepareData($model),
                    'type'       => sprintf('%s_%s', strtolower($modelName), $type),
                    'created_at' => now(),
                    'created_by' => auth('sanctum')->id(),
                ]);
            }

        } catch (Throwable) {
//            $this->error($e);
        }
    }

    /**
     * Get only changed column values
     * @param $model
     * @return array
     */
    public function prepareData($model): array
    {
        $data = [];

        $originals = $model->getRawOriginal();

        unset($originals['id']);
        unset($originals['created_at']);
        unset($originals['updated_at']);

        foreach ($originals as $column => $original) {

            try {
                /** @var $column */
                $attribute = $model->$column;

                // value by casts
                switch (true) {

                    case is_object($attribute) && get_class($attribute) === 'Illuminate\Support\Carbon':
                        $attribute = $attribute->format('Y-m-d H:i:s');
                        break;

                    case is_int($original):
                        $attribute = (int)$attribute;
                        break;

                    case is_object($attribute):
                    case is_array($original):
                        $attribute = collect($attribute)->toArray();
                        break;

                    case is_bool($original):
                        $attribute = (bool)$attribute;
                        break;

                }

                if ($original !== $attribute) {
                    $data[$column] = $original;
                }

            } catch (Throwable $e) {
                $this->error($e);
            }

        }

        return $data;

    }

}
