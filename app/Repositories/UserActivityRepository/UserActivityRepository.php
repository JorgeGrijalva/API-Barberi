<?php
declare(strict_types=1);

namespace App\Repositories\UserActivityRepository;

use App\Models\UserActivity;
use App\Repositories\CoreRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Schema;

class UserActivityRepository extends CoreRepository
{

    protected function getModelClass(): string
    {
        return UserActivity::class;
    }

    public function paginate(array $filter = []): LengthAwarePaginator
    {
        $column = $filter['column'] ?? 'id';

        if ($column !== 'id') {
            $column = Schema::hasColumn('user_activities', $column) ? $column : 'id';
        }

        return $this->model()
            ->filter($filter)
            ->orderBy($column, $filter['sort'] ?? 'desc')
            ->paginate($filter['perPage'] ?? 10);
    }
}
