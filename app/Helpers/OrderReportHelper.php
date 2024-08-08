<?php
declare(strict_types=1);

namespace App\Helpers;

use App\Models\Order;
use Illuminate\Support\Facades\DB;

class OrderReportHelper
{
    public static function rawPricesByOrderStatuses(): array
    {
        $statuses = Order::STATUSES;

        $raw = [];

        foreach ($statuses as $status) {
            $raw[] = DB::raw("sum(if(status = '$status', 1, 0)) as total_{$status}_count");
        }

        return $raw;
    }
}
