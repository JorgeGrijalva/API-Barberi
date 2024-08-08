<?php
declare(strict_types=1);

namespace App\Traits;

use App\Models\Like;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait Likable
{

    public function liked(): void
    {
        $userId = auth('sanctum')->id();

        $like = $this->likes()->firstWhere('user_id', $userId);

        if (empty($like)) {
            $this->likes()->create(['user_id' =>  $userId]);
            return;
        }

        $like->delete();
    }

    public function likes(): MorphMany
    {
        return $this->morphMany(Like::class, 'likable');
    }
}
