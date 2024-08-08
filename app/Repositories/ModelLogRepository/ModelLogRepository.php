<?php
declare(strict_types=1);

namespace App\Repositories\ModelLogRepository;

use App\Models\ModelLog;
use App\Repositories\CoreRepository;
use Illuminate\Contracts\Pagination\Paginator;
use Schema;

class ModelLogRepository extends CoreRepository
{
    /**
     * @return string
     */
    protected function getModelClass(): string
    {
        return ModelLog::class;
    }

    /**
     * This is only for users route
     * @param array $filter
     * @param string $paginate
     * @return Paginator
     */
    public function paginate(array $filter = [], string $paginate = 'simplePaginate'): Paginator
    {
        $column = $filter['column'] ?? 'id';

        if ($column !== 'id') {
            $column = Schema::hasColumn('model_logs', $column) ? $column : 'id';
        }

        /** @var ModelLog $model */
        $model = $this->model();

        return $model
            ->filter($filter)
            ->with([
                'createdBy:id,firstname,lastname',
            ])
            ->orderBy($column, $filter['sort'] ?? 'desc')
            ->$paginate($filter['perPage'] ?? 10);
    }

    /**
     * @param int $id
     * @return ModelLog|null
     */
    public function show(int $id): ?ModelLog
    {
        return $this->model()
            ->with([
                'createdBy',
                'modelType'
            ])
            ->find($id);
    }
}
