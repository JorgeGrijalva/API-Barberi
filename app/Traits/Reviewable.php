<?php
declare(strict_types=1);

namespace App\Traits;

use App\Models\Review;
use App\Models\User;
use DB;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait Reviewable
{
    public function addReview($data): void
    {
        /** @var Review $review */
        $review = $this->reviews()->updateOrCreate([
            'user_id'           => auth('sanctum')->id(),
            'reviewable_id'     => $this->id,
            'reviewable_type'   => self::class,
        ], [
            'rating'        => data_get($data, 'rating'),
            'comment'       => data_get($data, 'comment'),
            'cleanliness'   => data_get($data, 'cleanliness'),
            'masters'       => data_get($data, 'masters'),
            'location'      => data_get($data, 'location'),
            'price'         => data_get($data, 'price'),
            'interior'      => data_get($data, 'interior'),
            'service'       => data_get($data, 'service'),
            'communication' => data_get($data, 'communication'),
            'equipment'     => data_get($data, 'equipment'),
        ]);

        $this->selfUpdate($data, $review);

    }

    public function addAssignReview($data, $assignable): void
    {
        /** @var Review $review */
        $review = $this->reviews()->updateOrCreate([
            'user_id'           => auth('sanctum')->id(),
            'reviewable_id'     => $this->id,
            'reviewable_type'   => self::class,
            'assignable_id'     => $assignable->id,
            'assignable_type'   => get_class($assignable),
        ], [
            'rating'        => data_get($data, 'rating'),
            'comment'       => data_get($data, 'comment'),
            'cleanliness'   => data_get($data, 'cleanliness'),
            'masters'       => data_get($data, 'masters'),
            'location'      => data_get($data, 'location'),
            'price'         => data_get($data, 'price'),
            'interior'      => data_get($data, 'interior'),
            'service'       => data_get($data, 'service'),
            'communication' => data_get($data, 'communication'),
            'equipment'     => data_get($data, 'equipment'),
        ]);

        if ($assignable->id !== $this->id) {
            $assignableReviews = DB::table('reviews')
                ->select([
                    DB::raw('count(id) as count'),
                    DB::raw('sum(rating) as sum'),
                    DB::raw('avg(rating) as avg'),
                ])
                ->where([
                    'assignable_id'   => $assignable->id,
                    'assignable_type' => get_class($assignable),
                ])
                ->first();

            $assignable->update([
                'r_count' => $assignableReviews?->count ?? 0,
                'r_sum'   => round($assignableReviews?->sum, 1) ?? 0,
                'r_avg'   => round($assignableReviews?->avg, 1) ?? 0
            ]);
        }

        $this->selfUpdate($data, $review);

    }

    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    public function review(): MorphOne
    {
        return $this->morphOne(Review::class, 'reviewable');
    }

    public function selfUpdate($data, $review): void
    {
        $reviews = DB::table('reviews')
            ->select([
                DB::raw('count(id) as count'),
                DB::raw('sum(rating) as sum'),
                DB::raw('avg(rating) as avg'),
            ])
            ->where([
                'reviewable_id'   => $this->id,
                'reviewable_type' => self::class,
            ])
            ->first();

        $this->update([
            'r_count' => $reviews?->count ?? 0,
            'r_sum'   => round($reviews?->sum, 1) ?? 0,
            'r_avg'   => round($reviews?->avg, 1) ?? 0
        ]);

        $userReviews = DB::table('reviews')
            ->select([
                DB::raw('count(id) as count'),
                DB::raw('sum(rating) as sum'),
                DB::raw('avg(rating) as avg'),
            ])
            ->where('user_id', auth('sanctum')->id())
            ->first();

        /** @var User $user */
        $user = auth('sanctum')->user();

        $user?->update([
            'r_count' => $userReviews?->count ?? 0,
            'r_sum'   => round($userReviews?->sum, 1) ?? 0,
            'r_avg'   => round($userReviews?->avg, 1) ?? 0
        ]);

        if (!empty(data_get($data, 'images.0'))) {
            $review->galleries()->delete();

            $review->update([
                'img' => data_get($data, 'images.0'),
            ]);

            $review->uploads(data_get($data, 'images', []));
        }
    }
}
