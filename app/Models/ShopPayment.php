<?php
declare(strict_types=1);

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Schema;

/**
 * App\Models\ShopPayment
 *
 * @property int $id
 * @property int $payment_id
 * @property int $shop_id
 * @property int $status
 * @property string|null $client_id
 * @property string|null $secret_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $merchant_email
 * @property string|null $payment_key
 * @property-read Payment|null $payment
 * @property-read Shop|null $shop
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self filter(array $filter)
 * @method static Builder|self whereClientId($value)
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereMerchantEmail($value)
 * @method static Builder|self wherePaymentId($value)
 * @method static Builder|self wherePaymentKey($value)
 * @method static Builder|self whereSecretId($value)
 * @method static Builder|self whereShopId($value)
 * @method static Builder|self whereStatus($value)
 * @method static Builder|self whereUpdatedAt($value)
 * @mixin Eloquent
 */
class ShopPayment extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'status' => 'bool',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function scopeFilter($query, array $filter) {

        $column = $filter['column'] ?? 'id';

        if ($column !== 'id') {
            $column = Schema::hasColumn($column, 'shop_payments') ? $column : 'id';
        }

        $query
            ->when(data_get($filter, 'shop_id'),    fn($q, $shopId)     => $q->where('shop_id', $shopId))
            ->when(data_get($filter, 'payment_id'), fn($q, $paymentId)  => $q->where('payment_id', $paymentId))
            ->when(data_get($filter, 'status'),     fn($q, $status)     => $q->where('status', $status))
            ->when(data_get($filter, 'client_id'),  fn($q, $clientId)   => $q->where('client_id', $clientId))
            ->when(data_get($filter, 'secret_id'),  fn($q, $secretId)   => $q->where('secret_id', $secretId))
            ->orderBy($column, $filter['sort'] ?? 'desc');
    }
}
