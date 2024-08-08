<?php
declare(strict_types=1);

namespace App\Services\BonusService;

use App\Helpers\ResponseError;
use App\Models\Bonus;
use App\Services\CoreService;

class BonusService extends CoreService
{

    protected function getModelClass(): string
    {
        return Bonus::class;
    }

    public function create(array $data): array
    {
        $bonus = $this->model()->create($data);

        if (!$bonus) {
            return ['status' => false, 'code' => ResponseError::ERROR_501];
        }

        /** @var Bonus $bonus */
        $bonus->loadMissing('bonusStock')?->bonusStock?->update([
            'bonus_expired_at' => $bonus->expired_at
        ]);

        return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $bonus];
    }

    public function update(Bonus $bonus, array $data): array
    {
        $bonus->update($data);

        $bonus->bonusStock?->update([
            'bonus_expired_at' => $bonus->expired_at
        ]);

        return [
            'status' => true,
            'code'   => ResponseError::NO_ERROR,
            'data'   => $bonus,
        ];
    }

    public function delete(?array $ids = [], ?int $shopId = null): array
    {
        $bonuses = Bonus::with(['bonusStock'])
            ->whereIn('id', is_array($ids) ? $ids : [])
            ->where('shop_id', $shopId)
            ->get();

        foreach ($bonuses as $bonus) {
            /** @var Bonus $bonus */
            $bonus->bonusStock?->update([
                'bonus_expired_at' => null
            ]);
            $bonus->delete();
        }

        return ['status' => true, 'code' => ResponseError::NO_ERROR];
    }

    public function statusChange(int $id): array
    {
        /** @var Bonus $bonus */
        $bonus = $this->model()->with(['bonusStock'])->find($id);

        if (!$bonus) {
            return ['status' => false, 'code' => ResponseError::ERROR_404];
        }

        $bonus->update([
            'status' => !$bonus->status
        ]);

        $bonus->bonusStock?->update([
            'bonus_expired_at' => null
        ]);

        return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $bonus->loadMissing(['stock', 'bonusStock'])];
    }
}
