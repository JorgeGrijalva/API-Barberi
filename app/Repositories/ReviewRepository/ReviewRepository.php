<?php
declare(strict_types=1);

namespace App\Repositories\ReviewRepository;

use App\Helpers\Utility;
use App\Models\Language;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use App\Repositories\CoreRepository;
use DB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Schema;

class ReviewRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return Review::class;
    }

    public function paginate(array $filter, array $customWith = []): LengthAwarePaginator
    {
        $locale = Language::where('default', 1)->first()?->locale;

        $with = [
            'user' => fn($q) => $q->select([
                'id',
                'uuid',
                'firstname',
                'lastname',
                'email',
                'img',
            ]),
            'assignable',
            'galleries'
        ];

        $orderWith = [
            'reviewable.shop' => fn($q) => $q->select([
                'id',
                'uuid',
                'type',
            ]),
            'reviewable.shop.translation' => fn($q) => $q->select([
                'id',
                'locale',
                'title',
                'shop_id',
            ])
                ->where('locale', $this->language),
            'reviewable.shop.translations' => fn($q) => $q->select(['id']),
            'user' => fn($q) => $q->select([
                'id',
                'uuid',
                'firstname',
                'lastname',
                'email',
                'img',
            ]),
            'assignable',
//            'assignable.translation' => fn($q) => $q
//                ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
//                    $q->where('locale', $this->language)->orWhere('locale', $locale);
//                })),
        ];

        $productWith = [
            'reviewable' => fn($q) => $q->select([
                'id',
                'uuid',
                'shop_id',
                'img',
            ]),
            'reviewable.translations' => fn($q) => $q->select(['id']),
            'reviewable.translation' => fn($q) => $q->select([
                'id',
                'locale',
                'title',
                'description',
                'product_id',
            ])
                ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
            'assignable.translation' => fn($q) => $q
                ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
        ];

        if (count($customWith) > 0) {
            $with = $customWith;
        } else if (data_get($filter, 'type') === 'order') {
            $with += $orderWith;
        } else if (data_get($filter, 'type') === 'product') {
            $with += $productWith;
        } else if (data_get($filter, 'type') === 'shop' || data_get($filter, 'assign') === 'assign') {
            $with += [
                'assignable.translation' => fn($q) => $q
                    ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
            ];
        }

        /** @var Review $reviews */
        $reviews = $this->model();

        $column = $filter['column'] ?? 'id';

        /** @var User|null $user */
        $user = auth('sanctum')->user();

        if ($column !== 'user' || empty($user?->id)) {
            $column = Schema::hasColumn('reviews', $column) ? $column : 'id';
        }

        return $reviews
            ->filter($filter)
            ->with($with)
            ->when(
                $column === 'user' && !empty($user?->id),
                fn($q) => $q->orderByRaw(DB::raw("FIELD(user_id, $user?->id) DESC")),
                fn($q) => $q->orderBy($column, $filter['sort'] ?? 'desc')
            )
            ->paginate($filter['perPage'] ?? 10);
    }

    public function show(Review $review): Review
    {
        return $review->loadMissing(['reviewable', 'assignable', 'galleries', 'user']);
    }

    /**
     * @param int $id
     * @return array
     */
    public function reviewsGroupByRating(int $id): array
    {
        return Utility::reviewsGroupRating([
            'assignable_id'   => $id,
            'assignable_type' => User::class,
        ]);
    }

    public function addedReview(array $filter): array
    {
        $userId = data_get($filter, 'user_id');
        $type   = data_get($filter, 'type');
        $typeId = data_get($filter, 'type_id');

        if (empty($userId)) {
            return [
                'ordered'       => false,
                'added_review'  => false,
            ];
        }

        switch ($type) {

            case 'shop':

                $ordered = Order::where('user_id', $userId)
                    ->where('status', Order::STATUS_DELIVERED)
                    ->whereHas('orderDetails', fn($q) => $q->where('shop_id', $typeId))
                    ->exists();

                break;
            case 'product':

                /** @var Product|null $product */
                $product = Product::with('stocks:id,product_id')->find($typeId);

                $ordered = OrderDetail::whereHas('order',
                    fn($q) => $q->where('user_id', $userId)->where('status', Order::STATUS_DELIVERED)
                )
                    ->whereIn('stock_id', $product?->stocks?->pluck('id')?->toArray())
                    ->exists();

                break;

            default:
                $ordered = false;
        }

        $addedReview = Review::filter($filter)->exists();

        return [
            'ordered'       => $ordered,
            'added_review'  => $addedReview,
        ];
    }

    public function modelOptions(array $filter = []): array
    {
        /** @var Review $options */
        $options = Review::filter($filter)
            ->select([
                DB::raw('avg(if(cleanliness   >= 1, cleanliness,   0)) as cleanliness'),
                DB::raw('avg(if(masters       >= 1, masters,       0)) as masters'),
                DB::raw('avg(if(location      >= 1, location,      0)) as location'),
                DB::raw('avg(if(price         >= 1, price,         0)) as price'),
                DB::raw('avg(if(interior      >= 1, interior,      0)) as interior'),
                DB::raw('avg(if(service       >= 1, service,       0)) as service'),
                DB::raw('avg(if(communication >= 1, communication, 0)) as communication'),
                DB::raw('avg(if(equipment     >= 1, equipment,     0)) as equipment'),
            ])
            ->first();

        return [
            'cleanliness'   => round((double)$options?->cleanliness,   1) * 10,
            'masters'       => round((double)$options?->masters,       1) * 10,
            'location'      => round((double)$options?->location,      1) * 10,
            'price'         => round((double)$options?->price,         1) * 10,
            'interior'      => round((double)$options?->interior,      1) * 10,
            'service'       => round((double)$options?->service,       1) * 10,
            'communication' => round((double)$options?->communication, 1) * 10,
            'equipment'     => round((double)$options?->equipment,     1) * 10,
        ];
    }
}
