<?php
declare(strict_types=1);

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\PaymentProcess
 *
 * @property string $id
 * @property int $user_id
 * @property string $model_type
 * @property int $model_id
 * @property array $data
 * @property User|null $user
 * @property Order|Cart|DigitalFile|ParcelOrder|ShopAdsPackage|Subscription|null $model
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self filter($filter)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereUserId($value)
 * @method static Builder|self whereModelId($value)
 * @method static Builder|self whereModelType($value)
 * @method static Builder|self whereData($value)
 * @mixin Eloquent
 */
class PaymentProcess extends Model
{
    public $table = 'payment_process';
    public $guarded = [];
    public $timestamps = false;

    public $casts = [
        'id'   => 'string',
        'data' => 'array'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function model(): BelongsTo
    {
        return $this->morphTo('model');
    }
}
