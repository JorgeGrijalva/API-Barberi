<?php
declare(strict_types=1);

namespace App\Jobs;

use App\Repositories\FilterRepository\FilterRepository;
use App\Traits\Loggable;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\{InteractsWithQueue, SerializesModels};

class CashingFilter implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Loggable;

    /**
     * Create a new event instance.
     */
    public function __construct(private string $key, private array $filter) {}

    /**
     * Handle the event
     * @return void
     */
    public function handle(): void
    {
        try {
            (new FilterRepository)->cachingFilter($this->key, $this->filter);
        } catch (Exception $e) {
            Log::error($e->getMessage(), [$e->getCode(), $e->getLine()]);
        }
    }
}
