<?php
declare(strict_types=1);

namespace App\Services\BlogService;

use App\Helpers\ResponseError;
use App\Models\Blog;
use App\Services\CoreService;

class BlogReviewService extends CoreService
{
    protected function getModelClass(): string
    {
        return Blog::class;
    }

    public function addReview(Blog $blog, $collection): array
    {
        $blog->addReview($collection);

        return [
            'status' => true,
            'code'   => ResponseError::NO_ERROR,
            'data'   => $blog
        ];
    }

}
