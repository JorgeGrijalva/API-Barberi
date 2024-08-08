<?php
declare(strict_types=1);

namespace App\Repositories\NotificationRepository;

use App\Models\Notification;
use App\Repositories\CoreRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class NotificationRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return Notification::class;
    }

    /**
     * @return Collection|Notification
     */
    public function index(): array|Collection
    {
        return Notification::get();
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function paginate(array $filter = []): LengthAwarePaginator
    {
        return Notification::paginate($filter['perPage'] ?? 10);
    }

    /**
     * @param Notification $notification
     * @return Notification
     */
    public function show(Notification $notification): Notification
    {
        return $notification;
    }
}
