<?php
declare(strict_types=1);

namespace App\Repositories\UserWorkingDayRepository;

use App\Models\User;
use App\Models\UserWorkingDay;
use App\Repositories\CoreRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class UserWorkingDayRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return UserWorkingDay::class;
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function paginate(array $filter = []): LengthAwarePaginator
    {
        return User::filter($filter)
            ->with(['invite', 'workingDays'])
            ->whereHas('workingDays')
            ->paginate($filter['perPage'] ?? 10);
    }

    /**
     * @param int $userId
     * @return Collection
     */
    public function show(int $userId): Collection
    {
        return UserWorkingDay::select(['id', 'day', 'to', 'from', 'disabled'])
            ->where('user_id', $userId)
            ->orderBy('day')
            ->get();
    }
}
