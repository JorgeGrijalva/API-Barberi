<?php
declare(strict_types=1);

namespace App\Services\UserServices;

use App\Helpers\ResponseError;
use App\Jobs\UserActivityJob;
use App\Models\Product;
use App\Models\User;
use App\Models\UserActivity;
use App\Services\CoreService;
use Exception;
use Jenssegers\Agent\Agent;

class UserActivityService extends CoreService
{
    /**
     * @return string
     */
    protected function getModelClass(): string
    {
        return UserActivity::class;
    }

    /**
     * @param int $modelId
     * @param string $modelType
     * @param string $type
     * @param string|int $value
     * @param User|null $user
     * @return array
     */
    public function create(int $modelId, string $modelType, string $type, string|int $value, User|null $user): array
    {
        try {
            $agent = new Agent;

            $attributes = [
                'model_id'          => $modelId,
                'model_type'        => $modelType,
                'device'            => $agent->device(),
                'ip'                => request()->ip(),
                'agent->browser'    => $agent->browser(),
            ];

            $values = [
                'type'       => $type,
                'ip'         => request()->ip(),
                'device'     => $agent->device(),
                'agent'      => [
                    'device'        => $agent->device(),
                    'platform'      => $agent->platform(),
                    'browser'       => $agent->browser(),
                    'robot'         => $agent->robot(),
                    'deviceType'    => $agent->deviceType(),
                    'languages'     => $agent->languages(),
                    'getUserAgent'  => $agent->getUserAgent(),
                    'ip'            => request()->ip(),
                ]
            ];

            $activity = !empty($user) ?
                $user->activities()->updateOrCreate($attributes, $values) :
                UserActivity::updateOrCreate($attributes, $values);

            $activity->value .= "| $value";

            if (is_int($value)) {
                $activity->value = ((int)$activity->value ?? 0) + $value;
            }

            $activity->save();

            return [
                'status'  => true,
                'code'    => ResponseError::NO_ERROR,
                'message' => __('errors.' . ResponseError::NO_ERROR, locale: $this->language)
            ];
        } catch (Exception $e) {
            $this->error($e);
            return [
                'status'    => false,
                'code'      => $e->getCode(),
                'message'   => $e->getMessage()
            ];
        }
    }

    public function createMany(array $ids = []): array
    {
        try {
            foreach ($ids as $id) {
                $product = Product::find($id);
                if (empty($product)) {
                    continue;
                }
                UserActivityJob::dispatchAfterResponse(
                    $product->id,
                    get_class($product),
                    'click',
                    1,
                    auth('sanctum')->user()
                );
            }

            return [
                'status'  => true,
                'code'    => ResponseError::NO_ERROR,
                'message' => __('errors.' . ResponseError::NO_ERROR, locale: $this->language)
            ];
        } catch (Exception $e) {
            return [
                'status'    => false,
                'code'      => ResponseError::ERROR_501,
                'message'   => $e->getMessage()
            ];
        }
    }
}
