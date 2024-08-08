<?php
declare(strict_types=1);

namespace App\Jobs;

use App\Models\Currency;
use App\Traits\Loggable;
use DB;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\{InteractsWithQueue, SerializesModels};

class UpdateWalletCurrencyToDefault implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Loggable;

    /**
     * Create a new event instance.
     */
    public function __construct(public Currency $currency) {}

    /**
     * Handle the event
     * @return void
     */
    public function handle(): void
    {
        try {
            DB::table('wallets')
                ->update([
                    'currency_id' => $this->currency->id
                ]);

        } catch (Exception $e) {
            Log::error($e->getMessage(), [$e->getCode(), $e->getLine()]);
        }
    }
}
