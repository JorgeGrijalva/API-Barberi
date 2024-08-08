<?php
declare(strict_types=1);

namespace App\Repositories\PaymentRepository;

use App\Models\Payment;
use App\Repositories\CoreRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Schema;

class PaymentRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return Payment::class;
    }

    public function paginate(array $filter): LengthAwarePaginator
    {
        $column = $filter['column'] ?? 'id';

        if ($column !== 'id') {
            $column = Schema::hasColumn('payments', $column) ? $column : 'id';
        }

        /** @var Payment $payment */
        $payment = $this->model();

        return $payment
            ->when(data_get($filter, 'active'), function ($q, $active) {
                $q->where('active', $active);
            })
            ->orderBy($column, $filter['sort'] ?? 'desc')
            ->paginate($filter['perPage'] ?? 10);
    }

    public function paymentsList(array $filter): Collection
    {
        $column = $filter['column'] ?? 'id';

        if ($column !== 'id') {
            $column = Schema::hasColumn('payments', $column) ? $column : 'id';
        }

        /** @var Payment $payment */
        $payment = $this->model();

        return $payment
            ->when(data_get($filter, 'active'), function ($q, $active) {
                $q->where('active', $active);
            })
            ->orderBy($column, $filter['sort'] ?? 'desc')
            ->get();
    }

    public function paymentDetails(int $id)
    {
        return $this->model()->find($id);
    }
}
