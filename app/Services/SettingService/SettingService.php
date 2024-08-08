<?php
declare(strict_types=1);

namespace App\Services\SettingService;

use App\Models\Settings;
use App\Services\CoreService;

class SettingService extends CoreService
{
    protected function getModelClass(): string
    {
        return Settings::class;
    }
}
