<?php
declare(strict_types=1);

namespace App\Repositories\PayoutsRepository;

use App\Models\Payout;
use App\Repositories\CoreRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Schema;

class PayoutsRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return Payout::class;
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function paginate(array $filter = []): LengthAwarePaginator
    {
        $column = $filter['column'] ?? 'id';

        if ($column !== 'id') {
            $column = Schema::hasColumn('payouts', $column) ? $column : 'id';
        }

        return Payout::filter($filter)->with([
            'currency',
            'payment',
            'createdBy:id,uuid,firstname,lastname,img,active',
            'createdBy.wallet',
            'approvedBy:id,uuid,firstname,lastname,img,active',
            'approvedBy.wallet',
        ])
            ->orderBy($column, $filter['sort'] ?? 'desc')
            ->paginate($filter['perPage'] ?? 10);
    }

    /**
     * @param Payout $payout
     * @return Payout
     */
    public function show(Payout $payout): Payout
    {
        return $payout->load([
            'currency',
            'payment',
            'createdBy:id,uuid,firstname,lastname,img,active',
            'createdBy.wallet',
            'approvedBy:id,uuid,firstname,lastname,img,active',
            'approvedBy.wallet',
        ]);
    }
}
