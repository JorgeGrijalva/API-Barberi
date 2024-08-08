<?php
declare(strict_types=1);

namespace App\Models;

use App\Traits\Payable;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * App\Models\PaymentToPartner
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $model_id
 * @property string|null $model_type
 * @property string $type
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $user
 * @property-read Order|Booking|null $model
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereUpdatedAt($value)
 * @method static Builder|self whereId($value)
 * @mixin Eloquent
 */
class PaymentToPartner extends Model
{
    use HasFactory, Payable;

    protected $guarded = ['id'];

	const SELLER	  = 'seller';
	const DELIVERYMAN = 'deliveryman';

	const TYPES = [
		self::SELLER 	  => self::SELLER,
		self::DELIVERYMAN => self::DELIVERYMAN,
	];

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo
     */
    public function model(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

	public function scopeFilter($query, array $filter) {
		$query
            ->when(data_get($filter, 'shop_id'), function (Builder $q, $shopId) {
                $q->whereHas('model', fn($q) => $q->where('shop_id', $shopId));
            })
			->when(data_get($filter, 'order_id'),   fn($q, $id)     => $q->where('model_id', $id)->where('model_type', Order::class))
			->when(data_get($filter, 'booking_id'), fn($q, $id)     => $q->where('model_id', $id)->where('model_type', Booking::class))
			->when(data_get($filter, 'user_id'),    fn($q, $userId) => $q->where('user_id',  $userId))
			->when(data_get($filter, 'type'),       fn($q, $type)   => $q->where('type',     $type));
	}
}
