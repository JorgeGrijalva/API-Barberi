<?php
declare(strict_types=1);

namespace App\Services\BackUpService;

use App\Models\User;
use App\Services\CoreService;

class ModuleService extends CoreService
{
    protected function getModelClass(): string
    {
        return User::class;
    }
}
