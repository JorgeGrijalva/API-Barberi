<?php
declare(strict_types=1);

namespace App\Services\MemberShipService;

use App\Models\UserMemberShip;
use App\Services\CoreService;
use App\Traits\SetTranslations;

class UserMemberShipService extends CoreService
{
    use SetTranslations;

    protected function getModelClass(): string
    {
        return UserMemberShip::class;
    }

    public function delete(?array $ids = [], array $filter = []): void
    {
        $models = UserMemberShip::filter($filter)->find(is_array($ids) ? $ids : []);

        foreach ($models as $model) {
            $model->delete();
        }

    }

}
