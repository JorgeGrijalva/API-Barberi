<?php
declare(strict_types=1);

namespace App\Traits;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Collection;

trait SetCurrency
{
    public function currency(): float
    {
        /** @var Collection $list */
        $list = Currency::currenciesList();

        $rate = $list->where('id', (int)request('currency_id'))->first()?->rate;
        $rate = $rate ?? ($list->where('default', 1)->first()?->rate ?? 1);

        return (float)($rate <= 0 ? 1 : $rate);
    }

}
