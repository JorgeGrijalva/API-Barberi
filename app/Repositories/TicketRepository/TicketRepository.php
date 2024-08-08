<?php
declare(strict_types=1);

namespace App\Repositories\TicketRepository;

use App\Models\Ticket;
use App\Repositories\CoreRepository;
use Illuminate\Support\Facades\Cache;
use Schema;

class TicketRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return Ticket::class;
    }

    public function paginate(array $filter)
    {
        if (!Cache::get('rjkcvd.ewoidfh') || data_get(Cache::get('rjkcvd.ewoidfh'), 'active') != 1) {
            abort(403);
        }

        $column = $filter['column'] ?? 'id';

        if ($column !== 'id') {
            $column = Schema::hasColumn('tickets', $column) ? $column : 'id';
        }

        return $this->model()
            ->with('children')
            ->when(data_get($filter, 'created_by'), fn ($q, $createdBy) => $q->where('created_by', $createdBy))
            ->where('parent_id', 0)
            ->filter($filter)
            ->orderBy($column, $filter['sort'] ?? 'desc')
            ->paginate($filter['perPage'] ?? 10);
    }
}
