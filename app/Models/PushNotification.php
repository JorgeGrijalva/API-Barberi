<?php
declare(strict_types=1);

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * App\Models\PushNotification
 *
 * @property int $id
 * @property int $model_id
 * @property string $model_type
 * @property string $type
 * @property string $title
 * @property string $body
 * @property array $data
 * @property int $user_id
 * @property User $user
 * @property User|Order|Blog|null $model
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $read_at
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereUpdatedAt($value)
 * @mixin Eloquent
 */
class PushNotification extends Model
{
    protected $guarded = ['id'];
    protected $casts   = [
        'data' => 'array',
    ];

    const NEW_ORDER              = 'new_order';
    const NEW_MESSAGE            = 'new_message';
    const RE_BOOKING             = 're_booking';
    const NEW_BOOKING            = 'new_booking';
    const NEWS_PUBLISH           = 'news_publish';
    const ADD_CASHBACK           = 'add_cashback';
    const SHOP_APPROVED          = 'shop_approved';
    const WALLET_TOP_UP          = 'wallet_top_up';
    const INVITE_MASTER          = 'invite_master';
    const STATUS_CHANGED         = 'status_changed';
    const WALLET_WITHDRAW        = 'wallet_withdraw';
    const BOOKING_UPDATED        = 'booking_updated';
    const NEW_PARCEL_ORDER       = 'new_parcel_order';
    const NEW_USER_BY_REFERRAL   = 'new_user_by_referral';
    const BOOKING_NOTIFICATION   = 'booking_notification';
    const BOOKING_STATUS_CHANGED = 'booking_status_changed';
    const NOTIFICATIONS         = 'notifications';

    const TYPES = [
        self::NEW_ORDER              => self::NEW_ORDER,
        self::RE_BOOKING             => self::RE_BOOKING,
        self::NEW_BOOKING            => self::NEW_BOOKING,
        self::NEWS_PUBLISH           => self::NEWS_PUBLISH,
        self::ADD_CASHBACK           => self::ADD_CASHBACK,
        self::SHOP_APPROVED          => self::SHOP_APPROVED,
        self::WALLET_TOP_UP          => self::WALLET_TOP_UP,
        self::INVITE_MASTER          => self::INVITE_MASTER,
        self::STATUS_CHANGED         => self::STATUS_CHANGED,
        self::BOOKING_UPDATED        => self::BOOKING_UPDATED,
        self::WALLET_WITHDRAW        => self::WALLET_WITHDRAW,
        self::NEW_PARCEL_ORDER       => self::NEW_PARCEL_ORDER,
        self::NEW_USER_BY_REFERRAL   => self::NEW_USER_BY_REFERRAL,
        self::BOOKING_NOTIFICATION   => self::BOOKING_NOTIFICATION,
        self::BOOKING_STATUS_CHANGED => self::BOOKING_STATUS_CHANGED,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function model(): MorphTo
    {
        return $this->morphTo('model');
    }
}
