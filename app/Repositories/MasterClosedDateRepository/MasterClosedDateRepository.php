<?php
declare(strict_types=1);

namespace App\Repositories\MasterClosedDateRepository;

use App\Models\MasterClosedDate;
use App\Repositories\CoreRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Schema;

class MasterClosedDateRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return MasterClosedDate::class;
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function paginate(array $filter = []): LengthAwarePaginator
    {
        $column = $filter['column'] ?? 'id';

        if ($column !== 'id') {
            $column = Schema::hasColumn('master_closed_dates', $column) ? $column : 'id';
        }

        return MasterClosedDate::filter($filter)
            ->with(['master'])
            ->orderBy($column, $filter['sort'] ?? 'desc')
            ->paginate($filter['perPage'] ?? 10);
    }

    /**
     * @param int $masterId
     * @return Collection
     */
    public function show(int $masterId): Collection
    {
        return MasterClosedDate::where('master_id', $masterId)
            ->orderBy('date')
            ->get();
    }
}
