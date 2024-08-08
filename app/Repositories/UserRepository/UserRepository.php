<?php
declare(strict_types=1);

namespace App\Repositories\UserRepository;

use App\Models\Language;
use App\Models\Notification;
use App\Models\Order;
use App\Models\Referral;
use App\Models\Settings;
use App\Models\Transaction;
use App\Models\User;
use App\Repositories\CoreRepository;
use DB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Schema;

class UserRepository extends CoreRepository
{
    protected function getModelClass(): string
    {
        return User::class;
    }

    /**
     * @param int $id
     * @return User|null
     */
    public function userById(int $id): ?User
    {
        $locale = Language::where('default', 1)->first()?->locale;

        /** @var User $user */
        $user = $this->model()
            ->with([
                'roles',
                'wallet',
                'shop',
                'point',
                'emailSubscription',
                'notifications',
//                'assignReviews',
                'invite',
                'currency',
                'workingDays',
                'translation' => fn($q) => $q
                    ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                        $q->where('locale', $this->language)->orWhere('locale', $locale);
                    })),
                'translations',
                'memberShips' => fn($q) => $q->orderBy('expired_at', 'desc')
            ])
            ->find($id);

        if (empty($user?->wallet)) {
            return $user;
        }

        $referralActive = (int)Settings::where('key', 'referral_active')->first()?->value;
        $locale = Language::where('default', 1)->first()?->locale;

        if ($referralActive) {

            /** @var Referral $referral */
            $referral = Referral::with([
                'translation' => fn($query) => $query->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                }),
                'translations',
                'galleries',
            ])->where([
                ['expired_at', '>=', now()],
            ])->first();

            if ($referral?->id) {
                $rate         = $user->wallet->currency?->rate ?? 1;

                $fromTopUp    = $user->wallet->histories?->where('type','referral_from_topup');
                $fromWithdraw = $user->wallet->histories?->where('type','referral_from_withdraw');

                $toTopUp      = $user->wallet->histories?->where('type','referral_to_topup');
                $toWithdraw   = $user->wallet->histories?->where('type','referral_to_withdraw');

                $user
                    ->setAttribute('referral_from_topup_price', $fromTopUp?->sum('price') * $rate)
                    ->setAttribute('referral_from_withdraw_price', $fromWithdraw?->sum('price') * $rate)
                    ->setAttribute('referral_from_topup_count', $fromTopUp?->count())
                    ->setAttribute('referral_from_withdraw_count', $fromWithdraw?->count())

                    ->setAttribute('referral_to_topup_price', $toTopUp?->sum('price') * $rate)
                    ->setAttribute('referral_to_withdraw_price', $toWithdraw?->sum('price') * $rate)
                    ->setAttribute('referral_to_topup_count', $toTopUp?->count())
                    ->setAttribute('referral_to_withdraw_count', $toWithdraw?->count());
            }

            // for UserResource
            request()->offsetSet('referral', 1);
        }

        return $user;
    }

    public function chatShowById(int $id) {
        return User::select([
            'id',
            'firstname',
            'lastname',
            'img',
            'active',
        ])->find($id);
    }

    public function chatUsersGet(array $ids): \Illuminate\Support\Collection
    {
        return User::select([
            'id',
            'firstname',
            'lastname',
            'img',
            'active',
        ])
            ->whereIn('id', array_unique($ids))
            ->get();
    }

    public function adminInfo(): ?User
    {
        return User::whereHas('roles', fn($q) => $q->where('name', 'admin'))
            ->select([
                'id',
                'firstname',
                'lastname',
                'img',
            ])
            ->first();
    }

    public function userByUUID(string $uuid): Model|Builder|null
    {
        $locale = Language::where('default', 1)->first()?->locale;

        return $this->model()
            ->with([
                'shop.translation' => fn($q) => $q
                    ->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                        $q->where('locale', $this->language)->orWhere('locale', $locale);
                    })),
                'wallet',
                'point',
                'deliveryManSetting',
                'roles',
                'currency',
                'invitations.shop:id',
                'invitations.shop.translation' => fn($q) => $q
                    ->select(['id', 'shop_id', 'locale', 'title'])
                    ->where('locale', $this->language)->orWhere('locale', $locale),
                'workingDays',
                'translation' => fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale),
                'translations'
            ])
            ->firstWhere('uuid', $uuid);
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function usersPaginate(array $filter = []): LengthAwarePaginator
    {
        $locale = Language::where('default', 1)->first()?->locale;

        return $this->model()
            ->filter($filter)
            ->with([
                'shop',
                'wallet',
                'invitations.shop:id',
                'invitations.shop.translation' => fn($q) => $q
                    ->select(['id', 'shop_id', 'locale', 'title'])
                    ->where('locale', $this->language)->orWhere('locale', $locale),
                'deliveryManSetting',
                'roles' => fn($q)  => $q->when(data_get($filter, 'role'), function ($q, $role) {
                    $q->where('name', $role);
                }),
                'workingDays' => fn($q) => $q->when(data_get($filter, 'work_24_7'),
                    fn($b) => $b->where('from', '01-00')->where('to', '>=', '23-00')
                ),
                'translation' => fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale),
                'translations',
            ])
            ->paginate($filter['perPage'] ?? 10);
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function selectUsers(array $filter = []): LengthAwarePaginator
    {
        return $this->model()
            ->filter($filter)
            ->select([
                'id',
                'uuid',
                'firstname',
                'lastname',
                'img',
                'active',
            ])
            ->paginate($filter['perPage'] ?? 10);
    }

    /**
     * @param int|string $id
     * @return User|null
     */
    public function selectUserById(int|string $id): ?User
    {
        $user = User::where('uuid', $id)
            ->select([
                'id',
                'uuid',
                'firstname',
                'lastname',
                'img',
                'active',
            ])
            ->first();

        if (empty($user) || $user->uuid !== $id) {
            $user = User::where('id', $id)
                ->select([
                    'id',
                    'uuid',
                    'firstname',
                    'lastname',
                    'img',
                    'active',
                ])
                ->first();
        }

        return $user;
    }

    public function usersSearch(array $filter): LengthAwarePaginator
    {
        $locale = Language::where('default', 1)->first()?->locale;
        $column = $filter['column'] ?? 'id';

        if ($column !== 'id') {
            $column = Schema::hasColumn('users', $column) ? $column : 'id';
        }

        return $this->model()
            ->filter($filter)
            ->with([
                'roles' => fn($q) => $q->when(data_get($filter, 'roles'), function ($q, $roles) {
                    $q->whereIn('name', is_array($roles) ? $roles : [$roles]);
                }),
                'translation' => fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale),
            ])
            ->orderBy($column, data_get($filter,'sort','desc'))
            ->paginate($filter['perPage'] ?? 10);
    }

    /**
     * @return Collection|Notification[]
     */
    public function usersNotifications(): array|Collection
    {
        return Notification::get();
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function deliveryMans(array $filter): LengthAwarePaginator
    {
        $filter['role'] = 'deliveryman';

        if (data_get($filter, 'empty-setting')) {
            $filter['online'] = false;
        }

        return $this->model()
            ->filter($filter)
            ->with([
                'roles',
                'assignReviews',
                'deliveryManSetting',
                'deliveryManOrders' => fn($q) => $q
                    ->select([
                        'id', 'total_price', 'status', 'deliveryman_id', 'location', 'address',
                        'delivery_fee', 'rate', 'delivery_date', 'user_id',
                        'username', 'current'
                    ])
                    ->when(data_get($filter, 'statuses'), function ($query, $statuses) {

                        if (!is_array($statuses)) {
                            return $query;
                        }

                        $statuses = array_intersect($statuses, Order::STATUSES);

                        return $query->whereIn('status', $statuses);
                    }),
                'deliveryManOrders.user:id,img,firstname,lastname',
                'wallet',
                'invitations'
            ])
            ->withCount('deliveryManOrders')
            ->withSum('deliveryManOrders', 'total_price')
            ->withSum('wallet', 'price')
            ->paginate($filter['perPage'] ?? 10);
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function shopUsersPaginate(array $filter = []): LengthAwarePaginator
    {
        $column = $filter['column'] ?? 'id';

        if ($column !== 'id') {
            $column = Schema::hasColumn('users', $column) ? $column : 'id';
        }

        return $this->model()
            ->filter($filter)
            ->with(['roles', 'invitations'])
            ->orderBy($column, $filter['sort'] ?? 'desc')
            ->paginate($filter['perPage'] ?? 10);
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator
     */
    public function searchSending(array $filter): LengthAwarePaginator
    {
        $column = $filter['column'] ?? 'id';

        if ($column !== 'id') {
            $column = Schema::hasColumn('users', $column) ? $column : 'id';
        }

        return $this->model()
            ->filter($filter)
            ->whereHas('wallet')
            ->select([
                'id',
                'uuid',
                'firstname',
                'lastname',
                'img',
            ])
            ->where('id', '!=', auth('sanctum')->id())
            ->orderBy($column, $filter['sort'] ?? 'desc')
            ->paginate($filter['perPage'] ?? 10);
    }

    /**
     * @return int[]
     */
    public function notificationStatistic(): array
    {
        $notification = DB::table('push_notifications')
            ->select([
                DB::raw('count(id) as count'),
                DB::raw("sum(if(type = 'new_order', 1, 0)) as total_new_order_count"),
                DB::raw("sum(if(type = 'new_user_by_referral', 1, 0)) as total_new_user_by_referral_count"),
                DB::raw("sum(if(type = 'status_changed', 1, 0)) as total_status_changed_count"),
                DB::raw("sum(if(type = 'news_publish', 1, 0)) as total_news_publish_count"),
            ])
            ->whereNull('read_at')
            ->where('user_id', auth('sanctum')->id())
            ->first();

        $transaction = DB::table('transactions')
            ->select([
                DB::raw('count(id) as count'),
            ])
            ->where('status', Transaction::STATUS_PROGRESS)
            ->where('user_id', auth('sanctum')->id())
            ->first();

        return [
            'notification'          => (int)$notification?->count,
            'new_order'             => (int)$notification?->total_new_order_count,
            'new_user_by_referral'  => (int)$notification?->total_new_user_by_referral_count,
            'status_changed'        => (int)$notification?->total_status_changed_count,
            'news_publish'          => (int)$notification?->total_news_publish_count,
            'transaction'           => (int)$transaction?->count
        ];

    }
}
