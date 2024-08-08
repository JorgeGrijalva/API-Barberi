<?php
declare(strict_types=1);

namespace App\Services\LikeService;

use App\Helpers\ResponseError;
use App\Models\Like;
use App\Repositories\CoreRepository;
use App\Traits\Notification;
use DB;
use Throwable;

class LikeService extends CoreRepository
{
    use Notification;

    protected function getModelClass(): string
    {
        return Like::class;
    }

    /**
     * @param array $data
     * @return Like
     */
    public function store(array $data): Like
    {
        $data['likable_type'] = data_get(Like::TYPES, $data['type']);
        $data['likable_id']   = $data['type_id'];

        unset($data['type']);
        unset($data['type_id']);

        /** @var Like $like */
        $like = $this->model();

        return $like->updateOrCreate($data);
    }

    /**
     * @param array $data
     * @return array
     */
    public function storeMany(array $data): array
    {
        try {

            DB::transaction(function () use ($data) {

                foreach (data_get($data, 'types') as $type) {

                    $this->model()->updateOrCreate([
                        'likable_type' => Like::TYPES[$type['type']],
                        'likable_id'   => $type['type_id'],
                        'user_id'      => $data['user_id'],
                    ]);

                }

            });

            return [
                'status'  => true,
                'code'    => ResponseError::NO_ERROR,
                'message' => __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            ];
        } catch (Throwable $e) {
            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_501,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * @param Like $like
     * @param array $data
     * @return Like
     */
    public function update(Like $like, array $data): Like
    {
        $data['likable_type'] = data_get(Like::TYPES, $data['type']);
        $data['likable_id']   = $data['type_id'];

        $like->update($data);

        return $like;
    }

    /**
     * @param int $id
     * @param string|null $type
     * @return array
     */
    public function delete(int $id, ?string $type = 'product'): array
    {
        try {
            Like::where([
                'user_id'       => auth('sanctum')->id(),
                'likable_type'  => Like::TYPES[$type],
                'likable_id'    => $id,
            ])->delete();

            return [
                'status' => true,
                'code'   => ResponseError::NO_ERROR,
            ];
        } catch (Throwable $e) {
            $this->error($e);

            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_400,
                'message' => __('errors.' . ResponseError::ERROR_400, locale: $this->language)
            ];
        }
    }

}
