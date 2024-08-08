<?php
declare(strict_types=1);

namespace App\Models;

use App\Traits\Loadable;
use App\Traits\UserSearch;
use Database\Factories\ReviewFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * App\Models\Review
 *
 * @property int $id
 * @property string $reviewable_type
 * @property int $reviewable_id
 * @property string $assignable_type
 * @property int $assignable_id
 * @property int $user_id
 * @property float $rating
 * @property string|null $comment
 * @property string|null $img
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property bool|null $cleanliness
 * @property bool|null $masters
 * @property bool|null $location
 * @property bool|null $price
 * @property bool|null $interior
 * @property bool|null $service
 * @property bool|null $communication
 * @property bool|null $equipment
 * @property-read Collection|Gallery[] $galleries
 * @property-read int|null $galleries_count
 * @property-read Model|Eloquent $reviewable
 * @property-read Model|Eloquent $assignable
 * @property-read User $user
 * @method static ReviewFactory factory(...$parameters)
 * @method static Builder|self filter(array $filter)
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereComment($value)
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereImg($value)
 * @method static Builder|self whereRating($value)
 * @method static Builder|self whereReviewableId($value)
 * @method static Builder|self whereReviewableType($value)
 * @method static Builder|self whereUpdatedAt($value)
 * @method static Builder|self whereUserId($value)
 * @mixin Eloquent
 */
class Review extends Model
{
    use HasFactory, Loadable, UserSearch;

    protected $guarded = ['id'];

    const REVIEW_TYPES = [
        'blog',
        'order',
        'shop',
        'product',
        'booking',
    ];

    const ASSIGN_TYPES = [
        'shop',
        'user',
    ];

    public function reviewable(): MorphTo
    {
        return $this->morphTo('reviewable');
    }

    public function assignable(): MorphTo
    {
        return $this->morphTo('assignable');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeFilter($query, array $filter) {

        return $query
            ->when(data_get($filter, 'type'), function (Builder $query, $type) use ($filter) {

                if ($type === 'blog') {
                    $query->whereHasMorph('reviewable', Blog::class);
                } else if ($type === 'order') {
                    $query->whereHasMorph('reviewable', Order::class);
                } else if ($type === 'product') {
                    $query->whereHasMorph('reviewable', Product::class);
                } else if ($type === 'shop') {
                    $query->whereHasMorph('reviewable', Shop::class);
                } else if ($type === 'booking') {
                    $query->whereHasMorph('reviewable', Booking::class, function ($q) use ($filter) {
                        if (isset($filter['shop_id'])) {
                            $q->where('shop_id', $filter['shop_id']);
                        }
                    });
                }

                return $query->when(data_get($filter, 'type_id'), function ($q, $typeId) {
                    $q->where('reviewable_id', $typeId);
                });
            })
            ->when(data_get($filter, 'assign'), function (Builder $query, $assign) use ($filter) {

                if ($assign === 'user') {

                    $query->whereHasMorph('assignable', User::class, function ($q) {
                        $q->whereHas('roles', fn($r) => $r->where('name', 'user'));
                    });

                } else if ($assign === 'deliveryman') {

                    $query->whereHasMorph('assignable', User::class, function ($q) {
                        $q->whereHas('roles', fn($r) => $r->where('name', 'deliveryman'));
                    });

                } else if ($assign === 'master') {

                    $query->whereHasMorph('assignable', User::class, function ($q) {
                        $q->whereHas('roles', fn($r) => $r->where('name', 'master'));
                    });

                } else if ($assign === 'shop') {

                    $query->whereHasMorph('assignable', Shop::class);

                }

                return $query->when(data_get($filter, 'assign_id'), function ($q, $assignId) {
                    $q->where('assignable_id', $assignId);
                });
            })
            ->when(data_get($filter, 'date_from'), function (Builder $query, $dateFrom) use ($filter) {

                $dateTo = data_get($filter, 'date_to', date('Y-m-d'));

                $query->where(function (Builder $q) use($dateFrom, $dateTo) {
                    $q->where('created_at', '>=', $dateFrom)
                        ->where('created_at', '<=', $dateTo);
                });

            })
            ->when(data_get($filter, 'user_id'), function (Builder $query, $userId) {

                $query->where('user_id', $userId);

            })
            ->when(data_get($filter, 'search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q
                        ->where('comment', 'LIKE', "%$search%")
                        ->orWhereHas('user', fn($q) => $this->search($q, $search));
                });
            });
    }
}
