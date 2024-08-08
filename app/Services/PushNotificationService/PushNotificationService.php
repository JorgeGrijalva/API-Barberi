<?php
declare(strict_types=1);

namespace App\Services\PushNotificationService;

ini_set('memory_limit', '4G');
set_time_limit(0);

use App\Models\PushNotification;
use App\Repositories\CoreRepository;
use DB;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class PushNotificationService extends CoreRepository
{
    protected function getModelClass(): string
    {
        return PushNotification::class;
    }

    /**
     * @param array $data
     * @return Model|null
     */
    public function store(array $data): ?Model
    {
        return $this->model()->create($data);
    }

    /**
     * @param array $data
     * @param array $userIds
     * @param $model
     * @return bool
     */
    public function storeMany(array $data, array $userIds, $model): bool
    {
        $chunks = array_chunk($userIds, 2);

        foreach ($chunks as $chunk) {

            foreach ($chunk as $userId) {

                $newData = is_array(data_get($data, 'data')) ? $data['data'] : [data_get($data, 'data')];

                $data['user_id']    = $userId;
                $data['data']       = $newData;
                $data['model_id']   = $model->id;
                $data['model_type'] = get_class($model);

                try {
                    $this->model()->create($data);
                } catch (Throwable $e) {
                    $this->error($e);
                }
            }

        }

        return true;
    }

    /**
     * @param int $id
     * @param int $userId
     * @return Model|null
     */
    public function readAt(int $id, int $userId): ?Model
    {
        $model = $this->model()
            ->with('user')
            ->where('user_id', $userId)
            ->find($id);

        $model?->update([
            'read_at' => now()
        ]);

        return $model;
    }

    /**
     * @param int $userId
     * @return void
     */
    public function readAll(int $userId): void
    {
        dispatch(function () use ($userId) {
            DB::table('push_notifications')
                ->orderBy('id')
                ->where('user_id', $userId)
                ->update([
                    'read_at' => now()
                ]);
        })->afterResponse();
    }

    /**
     * @param int $id
     * @param int $userId
     * @return void
     */
    public function delete(int $id, int $userId): void
    {
        DB::table('push_notifications')
            ->where('id', $id)
            ->where('user_id', $userId)
            ->delete();
    }

    /**
     * @param int $userId
     * @return void
     */
    public function deleteAll(int $userId): void
    {
        DB::table('push_notifications')
            ->where('user_id', $userId)
            ->delete();
    }

}
