<?php
declare(strict_types=1);

namespace App\Traits;

use App\Models\Transaction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * @property-read Transaction|null $transaction
 * @property-read Collection|Transaction[] $transactions
 * @property-read int $transactions_count
 * */
trait Payable
{
    public function createTransaction(array $data): Model|Transaction
    {
        $status = data_get($data, 'status', Transaction::STATUS_PROGRESS);

        $data = [
            'price'                 => data_get($data, 'price'),
            'user_id'               => data_get($data, 'user_id', auth('sanctum')->id()),
            'payment_sys_id'        => data_get($data, 'payment_sys_id'),
            'payment_trx_id'        => data_get($data, 'payment_trx_id'),
            'note'                  => data_get($data, 'note', ''),
            'perform_time'          => data_get($data, 'perform_time', now()),
            'status_description'    => data_get($data, 'status_description', 'Transaction in progress'),
            'status'                => $status,
        ];

        if ($status == Transaction::STATUS_CANCELED) {
            $data['refund_time'] = now();
        }

        return $this->transactions()->updateOrCreate(['payable_id' => $this->id], $data);
    }

    public function transactions(): MorphMany
    {
        return $this->morphMany(Transaction::class, 'payable');
    }

    public function transaction(): MorphOne
    {
        return $this->morphOne(Transaction::class, 'payable');
    }
}
