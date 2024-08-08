<?php
declare(strict_types=1);

namespace App\Services\ReviewService;

use App\Models\Review;
use App\Services\CoreService;

class ReviewService extends CoreService
{
    protected function getModelClass(): string
    {
        return Review::class;
    }
}
