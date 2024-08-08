<?php
declare(strict_types=1);

namespace App\Observers;

use App\Models\PropertyGroup;
use App\Traits\Loggable;

class PropertyGroupObserver
{
    use Loggable;

    /**
     * Handle the Product "deleted" event.
     *
     * @param PropertyGroup $propertyGroup
     * @return void
     */
    public function deleted(PropertyGroup $propertyGroup): void
    {
//        $propertyGroup->propertyValues()->delete();
//        $propertyGroup->translations()->delete();
    }

}
